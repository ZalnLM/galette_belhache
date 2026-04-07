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
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
