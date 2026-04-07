<?php
declare(strict_types=1);

class Auth
{
    private const TWO_FACTOR_TTL = 600;

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['pending_2fa']);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'first_name' => (string)$user['first_name'],
            'last_name' => (string)$user['last_name'],
            'email' => (string)$user['email'],
            'role' => (string)$user['role'],
        ];
    }

    public static function beginTwoFactorChallenge(array $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['user']);
        $_SESSION['pending_2fa'] = [
            'user_id' => (int)$user['id'],
            'created_at' => time(),
        ];
    }

    public static function pendingTwoFactorUserId(): ?int
    {
        $pending = $_SESSION['pending_2fa'] ?? null;
        if (!is_array($pending)) {
            return null;
        }

        if ((int)($pending['created_at'] ?? 0) < time() - self::TWO_FACTOR_TTL) {
            unset($_SESSION['pending_2fa']);
            return null;
        }

        return (int)($pending['user_id'] ?? 0) ?: null;
    }

    public static function hasPendingTwoFactor(): bool
    {
        return self::pendingTwoFactorUserId() !== null;
    }

    public static function clearPendingTwoFactor(): void
    {
        unset($_SESSION['pending_2fa']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], '', (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::check() && (self::user()['role'] ?? '') === 'admin';
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Flash::set('warning', 'Connecte-toi pour acceder au site.');
            header('Location: /login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            Flash::set('danger', 'Acces reserve aux administrateurs.');
            header('Location: /');
            exit;
        }
    }
}
