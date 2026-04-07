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

        LoginThrottle::clear($email, $ip);
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
            'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, 1)',
            [$firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), 'utilisateur']
        );

        $user = $this->db->query('SELECT * FROM users WHERE id = ?', [$this->db->lastInsertId()])->fetch();
        Auth::login($user);
        Flash::set('success', 'Compte cree. Bienvenue sur l espace prive.');
        header('Location: /');
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
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'www.dev.galette.belhache.net');
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        );
        $scheme = $isHttps ? 'https' : 'http';
        $resetUrl = $scheme . '://' . $host . '/reset-password?token=' . urlencode($token);

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
}
