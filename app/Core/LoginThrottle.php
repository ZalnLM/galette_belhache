<?php
declare(strict_types=1);

class LoginThrottle
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;
    private const LOCK_SECONDS = 900;

    public static function isBlocked(string $email, string $ip): bool
    {
        $state = self::load($email, $ip);
        if ($state === null) {
            return false;
        }

        return (int)($state['blocked_until'] ?? 0) > time();
    }

    public static function remainingLockSeconds(string $email, string $ip): int
    {
        $state = self::load($email, $ip);
        if ($state === null) {
            return 0;
        }

        return max(0, (int)($state['blocked_until'] ?? 0) - time());
    }

    public static function recordFailure(string $email, string $ip): void
    {
        $now = time();
        $state = self::load($email, $ip) ?? [
            'attempts' => [],
            'blocked_until' => 0,
        ];

        $attempts = array_values(array_filter(
            (array)($state['attempts'] ?? []),
            static fn ($timestamp): bool => ((int)$timestamp >= ($now - self::WINDOW_SECONDS))
        ));
        $attempts[] = $now;

        $state['attempts'] = $attempts;
        $state['blocked_until'] = count($attempts) >= self::MAX_ATTEMPTS ? $now + self::LOCK_SECONDS : 0;

        self::persist($email, $ip, $state);
    }

    public static function clear(string $email, string $ip): void
    {
        $path = self::path($email, $ip);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function path(string $email, string $ip): string
    {
        $key = hash('sha256', mb_strtolower(trim($email)) . '|' . trim($ip));

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'lionels_galette_login_' . $key . '.json';
    }

    private static function load(string $email, string $ip): ?array
    {
        $path = self::path($email, $ip);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    private static function persist(string $email, string $ip, array $state): void
    {
        @file_put_contents(self::path($email, $ip), json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX);
    }
}
