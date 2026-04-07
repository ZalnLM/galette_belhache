<?php
declare(strict_types=1);

$secretsFile = __DIR__ . '/secrets.local.php';
$secrets = file_exists($secretsFile) ? require $secretsFile : [];
$secret = static function (string $key, string $default = '') use ($secrets): string {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string)$value;
    }

    return (string)($secrets[$key] ?? $default);
};

$env = strtolower((string)(getenv('APP_ENV') ?: 'dev'));

$configurations = [
    'dev' => [
        'host' => $secret('DB_HOST', '127.0.0.1'),
        'port' => $secret('DB_PORT', '3306'),
        'dbname' => $secret('DB_NAME', 'galettes_privees'),
        'user' => $secret('DB_USER', 'root'),
        'password' => $secret('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'prod' => [
        'host' => $secret('DB_HOST', '127.0.0.1'),
        'port' => $secret('DB_PORT', '3306'),
        'dbname' => $secret('DB_NAME', 'galettes_privees'),
        'user' => $secret('DB_USER', 'root'),
        'password' => $secret('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
];

define('DB_CONFIG', $configurations[$env] ?? $configurations['dev']);
