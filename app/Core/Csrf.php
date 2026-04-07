<?php
declare(strict_types=1);

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['_csrf'];
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(): void
    {
        $token = (string)($_POST['_csrf'] ?? '');
        if ($token === '' || !hash_equals((string)($_SESSION['_csrf'] ?? ''), $token)) {
            http_response_code(419);
            exit('Jeton CSRF invalide.');
        }
    }
}
