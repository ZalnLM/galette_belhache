<?php
declare(strict_types=1);

class AuthController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function login(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        View::render('auth/login', ['pageTitle' => 'Connexion']);
    }

    public function verifyEmail(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            Flash::set('danger', 'Le lien de validation est invalide.');
            header('Location: /login');
            exit;
        }

        $tokenHash = hash('sha256', $token);
        $user = $this->db->query(
            'SELECT id FROM users
             WHERE email_verification_token = ?
               AND email_verification_expires_at IS NOT NULL
               AND email_verification_expires_at >= NOW()
             LIMIT 1',
            [$tokenHash]
        )->fetch();

        if (!$user) {
            Flash::set('danger', 'Le lien de validation est invalide ou expire.');
            header('Location: /resend-verification');
            exit;
        }

        $this->db->query(
            'UPDATE users
             SET email_verified_at = NOW(),
                 email_verification_token = NULL,
                 email_verification_expires_at = NULL
             WHERE id = ?',
            [(int)$user['id']]
        );

        Flash::set('success', 'Adresse email validee. Tu peux maintenant te connecter.');
        header('Location: /login');
        exit;
    }

    public function resendVerification(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        View::render('auth/resend_verification', ['pageTitle' => 'Renvoyer la validation']);
    }

    public function storeResendVerification(): void
    {
        Csrf::verify();
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->db->query(
                'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
                [$email]
            )->fetch();

            if ($user && !$this->isEmailVerified($user)) {
                $this->issueEmailVerification((int)$user['id'], (string)$user['email'], (string)$user['first_name']);
            }
        }

        Flash::set('success', 'Si le compte existe et n est pas encore valide, un nouvel email de validation vient d etre envoye.');
        header('Location: /login');
        exit;
    }

    public function forgotPassword(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        View::render('auth/forgot_password', ['pageTitle' => 'Mot de passe oublie']);
    }

    public function storeForgotPassword(): void
    {
        Csrf::verify();
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = $this->db->query(
                'SELECT id, first_name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
                [$email]
            )->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

                $this->db->query(
                    'UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?',
                    [$tokenHash, $expiresAt, (int)$user['id']]
                );

                $this->sendPasswordResetEmail((string)$user['email'], (string)$user['first_name'], $token);
            }
        }

        Flash::set('success', 'Si un compte correspond a cet email, un lien de reinitialisation vient d etre envoye.');
        header('Location: /login');
        exit;
    }

    public function storeLogin(): void
    {
        Csrf::verify();
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        if (LoginThrottle::isBlocked($email, $ip)) {
            $remainingMinutes = (int)ceil(LoginThrottle::remainingLockSeconds($email, $ip) / 60);
            Flash::set('danger', 'Trop de tentatives de connexion. Reessaie dans ' . max(1, $remainingMinutes) . ' minute(s).');
            header('Location: /login');
            exit;
        }

        $user = $this->db->query(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [$email]
        )->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            LoginThrottle::recordFailure($email, $ip);
            usleep(350000);
            Flash::set('danger', 'Email ou mot de passe invalide.');
            header('Location: /login');
            exit;
        }

        if (!$this->isEmailVerified($user)) {
            $this->issueEmailVerification((int)$user['id'], (string)$user['email'], (string)$user['first_name']);
            Flash::set('warning', 'Ton adresse email doit etre validee avant la connexion. Un nouveau lien vient d etre envoye.');
            header('Location: /resend-verification');
            exit;
        }

        if ((int)($user['two_factor_enabled'] ?? 0) === 1 && (string)($user['two_factor_secret'] ?? '') !== '') {
            Auth::beginTwoFactorChallenge($user);
            header('Location: /two-factor');
            exit;
        }

        LoginThrottle::clear($email, $ip);
        Auth::login($user);
        header('Location: /');
        exit;
    }

    public function twoFactorChallenge(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        if (!Auth::hasPendingTwoFactor()) {
            Flash::set('warning', 'Commence par te connecter pour utiliser la verification en deux etapes.');
            header('Location: /login');
            exit;
        }

        View::render('auth/two_factor', ['pageTitle' => 'Verification 2FA']);
    }

    public function storeTwoFactorChallenge(): void
    {
        Csrf::verify();

        $pendingUserId = Auth::pendingTwoFactorUserId();
        if ($pendingUserId === null) {
            Flash::set('warning', 'La verification 2FA a expire. Reconnecte-toi.');
            header('Location: /login');
            exit;
        }

        $user = $this->db->query('SELECT * FROM users WHERE id = ? LIMIT 1', [$pendingUserId])->fetch();
        if (!$user || (int)($user['two_factor_enabled'] ?? 0) !== 1) {
            Auth::clearPendingTwoFactor();
            Flash::set('danger', 'Configuration 2FA invalide.');
            header('Location: /login');
            exit;
        }

        $code = trim((string)($_POST['code'] ?? ''));
        if (!Totp::verifyCode((string)$user['two_factor_secret'], $code)) {
            Flash::set('danger', 'Code de verification invalide.');
            header('Location: /two-factor');
            exit;
        }

        LoginThrottle::clear((string)$user['email'], trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')));
        Auth::login($user);
        header('Location: /');
        exit;
    }

    public function resetPassword(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        $token = trim((string)($_GET['token'] ?? ''));
        $user = $this->findResettableUserByToken($token);

        if (!$user) {
            Flash::set('danger', 'Le lien de reinitialisation est invalide ou expire.');
            header('Location: /forgot-password');
            exit;
        }

        View::render('auth/reset_password', [
            'pageTitle' => 'Nouveau mot de passe',
            'token' => $token,
        ]);
    }

    public function storeResetPassword(): void
    {
        Csrf::verify();
        $token = trim((string)($_POST['token'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        $user = $this->findResettableUserByToken($token);
        if (!$user) {
            Flash::set('danger', 'Le lien de reinitialisation est invalide ou expire.');
            header('Location: /forgot-password');
            exit;
        }

        if ($password !== $passwordConfirm) {
            Flash::set('danger', 'Les mots de passe ne correspondent pas.');
            header('Location: /reset-password?token=' . urlencode($token));
            exit;
        }

        if (mb_strlen($password) < 10) {
            Flash::set('danger', 'Le mot de passe doit contenir au moins 10 caracteres.');
            header('Location: /reset-password?token=' . urlencode($token));
            exit;
        }

        $this->db->query(
            'UPDATE users
             SET password_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL
             WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]
        );

        LoginThrottle::clear((string)$user['email'], trim((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')));

        Flash::set('success', 'Ton mot de passe a bien ete mis a jour. Tu peux maintenant te connecter.');
        header('Location: /login');
        exit;
    }

    public function register(): void
    {
        if (Auth::check()) {
            header('Location: /');
            exit;
        }

        View::render('auth/register', ['pageTitle' => 'Inscription']);
    }

    public function storeRegister(): void
    {
        Csrf::verify();
        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
            Flash::set('danger', 'Tous les champs sont obligatoires.');
            header('Location: /register');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('danger', 'Adresse email invalide.');
            header('Location: /register');
            exit;
        }

        if ($password !== $passwordConfirm) {
            Flash::set('danger', 'Les mots de passe ne correspondent pas.');
            header('Location: /register');
            exit;
        }

        if (mb_strlen($password) < 10) {
            Flash::set('danger', 'Le mot de passe doit contenir au moins 10 caracteres.');
            header('Location: /register');
            exit;
        }

        $exists = $this->db->query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
        if ($exists) {
            Flash::set('danger', 'Un compte existe deja avec cet email.');
            header('Location: /register');
            exit;
        }

        $this->db->query(
            'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active, email_verification_token, email_verification_expires_at, email_verified_at, two_factor_enabled)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, NULL, 0)',
            [
                $firstName,
                $lastName,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                'utilisateur',
                hash('sha256', $verificationToken = bin2hex(random_bytes(32))),
                (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s'),
            ]
        );

        $this->sendEmailVerification((string)$email, $firstName, $verificationToken);

        Flash::set('success', 'Compte cree. Verifie maintenant ton adresse email avant de te connecter.');
        header('Location: /login');
        exit;
    }

    public function profile(): void
    {
        Auth::requireLogin();
        $user = $this->db->query(
            'SELECT id, first_name, last_name, email, two_factor_secret, two_factor_enabled, email_verified_at
             FROM users WHERE id = ? LIMIT 1',
            [(int)Auth::user()['id']]
        )->fetch();

        $pendingSecret = (string)($_SESSION['two_factor_setup_secret'] ?? '');
        $otpAuthUri = '';
        if ($pendingSecret !== '') {
            $otpAuthUri = Totp::otpAuthUri(
                APP_NAME . ' (' . (string)$user['email'] . ')',
                $pendingSecret,
                APP_NAME
            );
        }

        View::render('auth/security', [
            'pageTitle' => 'Profil',
            'account' => $user,
            'emailVerified' => $this->isEmailVerified($user),
            'pendingSecret' => $pendingSecret,
            'otpAuthUri' => $otpAuthUri,
        ]);
    }

    public function prepareTwoFactor(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $_SESSION['two_factor_setup_secret'] = Totp::generateSecret();
        Flash::set('success', 'Cle 2FA preparee. Configure-la dans ton application d authentification puis valide avec un code.');
        header('Location: /profil');
        exit;
    }

    public function enableTwoFactor(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $secret = (string)($_SESSION['two_factor_setup_secret'] ?? '');
        $code = trim((string)($_POST['code'] ?? ''));

        if ($secret === '') {
            Flash::set('warning', 'Commence par preparer la configuration 2FA.');
            header('Location: /profil');
            exit;
        }

        if (!Totp::verifyCode($secret, $code)) {
            Flash::set('danger', 'Code 2FA invalide. Verifie la configuration de ton application.');
            header('Location: /profil');
            exit;
        }

        $this->db->query(
            'UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?',
            [$secret, (int)Auth::user()['id']]
        );

        unset($_SESSION['two_factor_setup_secret']);
        Flash::set('success', 'La verification en deux etapes est maintenant activee.');
        header('Location: /profil');
        exit;
    }

    public function disableTwoFactor(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $user = $this->db->query(
            'SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = ? LIMIT 1',
            [(int)Auth::user()['id']]
        )->fetch();

        if (!$user || (int)($user['two_factor_enabled'] ?? 0) !== 1) {
            Flash::set('warning', 'Le 2FA n est pas actif sur ce compte.');
            header('Location: /profil');
            exit;
        }

        $code = trim((string)($_POST['code'] ?? ''));
        if (!Totp::verifyCode((string)$user['two_factor_secret'], $code)) {
            Flash::set('danger', 'Code 2FA invalide.');
            header('Location: /profil');
            exit;
        }

        $this->db->query(
            'UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?',
            [(int)Auth::user()['id']]
        );

        unset($_SESSION['two_factor_setup_secret']);
        Flash::set('success', 'La verification en deux etapes a ete desactivee.');
        header('Location: /profil');
        exit;
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        header('Location: /login');
        exit;
    }

    private function findResettableUserByToken(string $token): ?array
    {
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $user = $this->db->query(
            'SELECT id, email
             FROM users
             WHERE password_reset_token = ?
               AND password_reset_expires_at IS NOT NULL
               AND password_reset_expires_at >= NOW()
             LIMIT 1',
            [$tokenHash]
        )->fetch();

        return $user ?: null;
    }

    private function sendPasswordResetEmail(string $email, string $firstName, string $token): void
    {
        $resetUrl = APP_BASE_URL . '/reset-password?token=' . urlencode($token);

        $subject = APP_NAME . ' - Reinitialisation du mot de passe';
        $message = "Bonjour " . trim($firstName) . ",\n\n"
            . "Une demande de reinitialisation de mot de passe a ete faite pour ton compte.\n"
            . "Tu peux definir un nouveau mot de passe via ce lien :\n"
            . $resetUrl . "\n\n"
            . "Ce lien est valable 1 heure.\n"
            . "Si tu n es pas a l origine de cette demande, tu peux ignorer cet email.\n";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . APP_NAME . ' <' . APP_SUPPORT_EMAIL . '>',
        ];

        $sent = @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("\r\n", $headers));
        if (!$sent) {
            error_log('Password reset email failed for ' . $email . ' - reset URL: ' . $resetUrl);
        }
    }

    private function issueEmailVerification(int $userId, string $email, string $firstName): void
    {
        $token = bin2hex(random_bytes(32));
        $this->db->query(
            'UPDATE users
             SET email_verification_token = ?, email_verification_expires_at = ?
             WHERE id = ?',
            [hash('sha256', $token), (new DateTimeImmutable('+24 hours'))->format('Y-m-d H:i:s'), $userId]
        );

        $this->sendEmailVerification($email, $firstName, $token);
    }

    private function sendEmailVerification(string $email, string $firstName, string $token): void
    {
        $verificationUrl = APP_BASE_URL . '/verify-email?token=' . urlencode($token);
        $subject = APP_NAME . ' - Validation de votre adresse email';
        $message = "Bonjour " . trim($firstName) . ",\n\n"
            . "Merci pour ton inscription.\n"
            . "Pour activer ton compte, valide ton adresse email via ce lien :\n"
            . $verificationUrl . "\n\n"
            . "Ce lien est valable 24 heures.\n";

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . APP_NAME . ' <' . APP_SUPPORT_EMAIL . '>',
        ];

        $sent = @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("\r\n", $headers));
        if (!$sent) {
            error_log('Verification email failed for ' . $email . ' - verification URL: ' . $verificationUrl);
        }
    }

    private function isEmailVerified(array $user): bool
    {
        if (!empty($user['email_verified_at'])) {
            return true;
        }

        return empty($user['email_verification_token']);
    }
}
