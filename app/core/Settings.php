<?php
declare(strict_types=1);

/** Key/value site settings stored in the `settings` table, cached per request. */
final class Settings
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Db::all('SELECT skey, svalue FROM settings') as $row) {
                    self::$cache[$row['skey']] = (string)$row['svalue'];
                }
            } catch (Throwable) {
                // Database not installed yet: fall back to defaults.
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::load();
        if (array_key_exists($key, $all) && $all[$key] !== '') {
            return $all[$key];
        }
        return $default !== '' ? $default : (string)(SettingsDef::defaults()[$key] ?? '');
    }

    public static function set(string $key, string $value): void
    {
        Db::run(
            'INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)',
            [$key, $value]
        );
        self::$cache = null;
    }
}
