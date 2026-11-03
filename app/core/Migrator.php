<?php
declare(strict_types=1);

/** Applies database/migrations/NNN_name.sql|php once each, in order. Used by the installer, the dashboard banner and tools/migrate.php. */
final class Migrator
{
    private static function files(): array
    {
        $files = glob(ROOT . '/database/migrations/*.{sql,php}', GLOB_BRACE) ?: [];
        sort($files);
        return $files;
    }

    private static function ensureTable(): void
    {
        Db::pdo()->exec('CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(120) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    /** @return string[] names of migrations not yet applied */
    public static function pending(): array
    {
        self::ensureTable();
        $done = array_column(Db::all('SELECT name FROM migrations'), 'name');
        return array_values(array_filter(array_map('basename', self::files()), fn($f) => !in_array($f, $done, true)));
    }

    /**
     * Same as pending(), but answers from a tiny flag file once everything is applied, so normal page views do no extra
     * database work. The flag is tied to the list of migration files, so new files trigger a fresh check automatically.
     * @return string[]
     */
    public static function pendingCached(): array
    {
        $flag = ROOT . '/storage/migrated.flag';
        $sig = md5(implode('|', array_map('basename', self::files())));
        if (is_file($flag) && trim((string)@file_get_contents($flag)) === $sig) {
            return [];
        }
        $pending = self::pending();
        if (!$pending) {
            @file_put_contents($flag, $sig);
        }
        return $pending;
    }

    /** @return string[] names applied now */
    public static function run(): array
    {
        require_once ROOT . '/app/Installer.php';
        $applied = [];
        foreach (self::pending() as $name) {
            $path = ROOT . '/database/migrations/' . $name;
            if (str_ends_with($name, '.sql')) {
                Installer::execSql(Db::pdo(), (string)file_get_contents($path));
            } else {
                $fn = require $path;
                $fn(Db::pdo());
            }
            Db::run('INSERT INTO migrations (name) VALUES (?)', [$name]);
            $applied[] = $name;
        }
        return $applied;
    }
}
