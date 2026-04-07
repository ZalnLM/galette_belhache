<?php
declare(strict_types=1);

class SchemaManager
{
    public static function ensure(): void
    {
        $db = Database::getInstance();
        $databaseName = (string)(DB_CONFIG['dbname'] ?? '');

        self::ensureColumn($db, $databaseName, 'users', 'email_verification_token', "ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(100) DEFAULT NULL AFTER password_reset_expires_at");
        self::ensureColumn($db, $databaseName, 'users', 'email_verification_expires_at', "ALTER TABLE users ADD COLUMN email_verification_expires_at DATETIME DEFAULT NULL AFTER email_verification_token");
        self::ensureColumn($db, $databaseName, 'users', 'email_verified_at', "ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL AFTER email_verification_expires_at");
        self::ensureColumn($db, $databaseName, 'users', 'two_factor_secret', "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(64) DEFAULT NULL AFTER email_verified_at");
        self::ensureColumn($db, $databaseName, 'users', 'two_factor_enabled', "ALTER TABLE users ADD COLUMN two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER two_factor_secret");

        $settingsTableExists = (int)$db->query(
            'SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?',
            [$databaseName, 'site_settings']
        )->fetchColumn();

        if ($settingsTableExists === 0) {
            $db->query(
                'CREATE TABLE site_settings (
                    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                    setting_value TEXT DEFAULT NULL,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }

        $db->query(
            'UPDATE users
             SET email_verified_at = NOW()
             WHERE email_verified_at IS NULL
               AND (email_verification_token IS NULL OR email_verification_token = "")'
        );
    }

    private static function ensureColumn(Database $db, string $databaseName, string $table, string $column, string $alterSql): void
    {
        $exists = (int)$db->query(
            'SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?',
            [$databaseName, $table, $column]
        )->fetchColumn();

        if ($exists === 0) {
            $db->query($alterSql);
        }
    }
}
