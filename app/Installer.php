<?php
declare(strict_types=1);

/** Creates the database, loads schema (and optional sample content), creates the admin account and writes config/.env. */
final class Installer
{
    /** @return string[] checklist problems; empty array = good to go */
    public static function problems(): array
    {
        $p = [];
        if (PHP_VERSION_ID < 80100) {
            $p[] = 'PHP 8.1 or newer is required (this server runs ' . PHP_VERSION . ').';
        }
        foreach (['pdo_mysql', 'mbstring', 'fileinfo'] as $ext) {
            if (!extension_loaded($ext)) {
                $p[] = "The PHP extension \"$ext\" must be enabled.";
            }
        }
        foreach (['config', 'storage', 'storage/logs', 'public/uploads', 'public/uploads/images', 'public/uploads/docs'] as $dir) {
            $path = ROOT . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            if (!is_dir($path) || !is_writable($path)) {
                $p[] = "The folder \"$dir\" must be writable.";
            }
        }
        return $p;
    }

    public static function notices(): array
    {
        $n = [];
        if (!extension_loaded('gd')) {
            $n[] = 'PHP "gd" is not enabled: uploaded photos will not be resized or cleaned.';
        }
        if (!extension_loaded('curl')) {
            $n[] = 'PHP "curl" is not enabled: DOI and ORCID import will not work (BibTeX and CSV import will).';
        }
        return $n;
    }

    /**
     * @param array $c host, port, name, user, pass, site_name, admin_name, admin_email, admin_password, sample (bool), env
     * @return string[] log lines
     */
    public static function run(array $c): array
    {
        $log = [];
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $c['name'])) {
            throw new RuntimeException('The database name may only contain letters, numbers and underscores.');
        }
        if (!filter_var($c['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please enter a valid administrator email.');
        }
        if (strlen($c['admin_password']) < 10) {
            throw new RuntimeException('The administrator password must be at least 10 characters.');
        }
        if (trim($c['admin_name']) === '') {
            throw new RuntimeException('Please enter the administrator name.');
        }

        try {
            $pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $c['host'], (int)$c['port']), $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Could not connect to MySQL: ' . $e->getMessage() . ' (Is WAMP running and the MySQL service green? Check the user, password and port.)');
        }
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $c['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $c['name'] . '`');
        $log[] = 'Database "' . $c['name'] . '" is ready.';

        $exists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($exists && (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            throw new RuntimeException('This database already contains an installation. Choose a different database name, or empty it in phpMyAdmin first.');
        }

        self::execSql($pdo, (string)file_get_contents(ROOT . '/database/schema.sql'));
        $log[] = 'Tables created.';
        if (!empty($c['sample'])) {
            self::execSql($pdo, (string)file_get_contents(ROOT . '/database/seed.sql'));
            $log[] = 'Sample content loaded.';
        } else {
            self::execSql($pdo, "INSERT INTO pages (slug, title, body) SELECT 'privacy', 'Privacy Policy', 'Add your privacy policy here.' FROM DUAL;"
                . "INSERT INTO pages (slug, title, body) SELECT 'terms', 'Terms of Use', 'Add your terms here.' FROM DUAL;");
        }

        // Later-phase tables and seed data (news engine, ...).
        require_once ROOT . '/config/config.php';
        require_once ROOT . '/app/core/Db.php';
        require_once ROOT . '/app/core/Migrator.php';
        require_once ROOT . '/app/core/Auth.php';
        require_once ROOT . '/app/core/Crypto.php';
        Db::useConnection($pdo);
        $applied = Migrator::run();
        if ($applied) {
            $log[] = 'Updates applied: ' . implode(', ', $applied) . '.';
        }

        $st = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, email_verified_at) VALUES (?, ?, ?, ?, NOW())');
        $st->execute([trim($c['admin_name']), strtolower(trim($c['admin_email'])), Auth::hash($c['admin_password']), 'admin']);
        $log[] = 'Administrator account created.';

        $set = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
        $set->execute(['site_name', trim($c['site_name']) ?: 'ClearSky Academy']);
        $set->execute(['contact_email', strtolower(trim($c['admin_email']))]);
        $set->execute(['notify_email', strtolower(trim($c['admin_email']))]);
        $set->execute(['owner_name', trim($c['admin_name']) !== '' ? trim($c['admin_name']) : 'Dr. [Your Name]']);

        // The website should not run as the database administrator: create an account that can reach only this database.
        $dbUser = $c['user'];
        $dbPass = $c['pass'];
        $local = in_array(strtolower($c['host']), ['127.0.0.1', 'localhost', '::1'], true);
        if (!empty($c['app_user'])) {
            if (!$local) {
                $log[] = 'Note: the database is on another server, so a dedicated database user was not created automatically. Create one with limited rights and put it in config/.env.';
            } else {
                try {
                    $appUser = 'csa_' . substr(preg_replace('/[^a-z0-9]/', '', strtolower($c['name'])), 0, 12) . '_' . bin2hex(random_bytes(3));
                    $appPass = bin2hex(random_bytes(24));
                    foreach (['localhost', '127.0.0.1'] as $h) {
                        $pdo->exec("CREATE USER '$appUser'@'$h' IDENTIFIED BY '$appPass'");
                        $pdo->exec('GRANT ALL PRIVILEGES ON `' . $c['name'] . "`.* TO '$appUser'@'$h'");
                    }
                    new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], (int)$c['port'], $c['name']), $appUser, $appPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $dbUser = $appUser;
                    $dbPass = $appPass;
                    $log[] = 'A dedicated database user (' . $appUser . ') with a long random password was created. It can reach only this database; the administrator login you typed is NOT stored.';
                } catch (Throwable $e) {
                    $log[] = 'Note: a dedicated database user could not be created (' . $e->getMessage() . '). The login you typed is used instead; you can switch later with: php tools/secure_config.php --dedicated-user';
                }
            }
        }
        $keyFile = Crypto::ensureKey();
        $log[] = 'Encryption key created at ' . $keyFile . ' (keep a safe backup copy of this file, separate from database backups).';

        $env = [
            '# Generated by the installer on ' . date('Y-m-d H:i'),
            'APP_ENV=' . ($c['env'] === 'production' ? 'production' : 'local'),
            'APP_DEBUG=' . ($c['env'] === 'production' ? '0' : '1'),
            'APP_URL=' . (preg_match('#^https://[A-Za-z0-9.-]+(:\d+)?$#', rtrim((string)($c['app_url'] ?? ''), '/')) ? rtrim((string)$c['app_url'], '/') : ''),
            'DB_HOST=' . $c['host'],
            'DB_PORT=' . (int)$c['port'],
            'DB_NAME=' . $c['name'],
            'DB_USER=' . $dbUser,
            $dbPass !== '' ? 'DB_PASS_ENC=' . Crypto::encrypt($dbPass, 'DB_PASS') : 'DB_PASS=',
            'MAIL_MODE=log',
            'MAIL_FROM=hello@clearskyacademy.com',
            'MAIL_FROM_NAME=' . self::envValue(trim($c['site_name']) ?: 'ClearSky Academy'),
            'SMTP_HOST=',
            'SMTP_PORT=587',
            'SMTP_SECURE=tls',
            'SMTP_USER=',
            'SMTP_PASS=',
            '# Behind a Cloudflare tunnel or reverse proxy on this machine the real visitor address is read from its header automatically.',
            '# TRUSTED_PROXIES=203.0.113.10',
            '# Optional: keep the encryption key outside the website folder (recommended on a live server):',
            '# APP_KEY_FILE=/home/you/keys/clearsky.key',
        ];
        if (file_put_contents(ROOT . '/config/.env', implode("\n", $env) . "\n", LOCK_EX) === false) {
            throw new RuntimeException('Could not write config/.env. Make the config folder writable and try again.');
        }
        @chmod(ROOT . '/config/.env', 0600);
        file_put_contents(ROOT . '/storage/install.lock', 'Installed ' . date('c') . "\n");
        $log[] = 'Configuration saved.';
        return $log;
    }

    private static function envValue(string $v): string
    {
        // Quote values with spaces, #, quotes or leading/trailing space so the .env parser reads them back unchanged.
        return preg_match('/[\s#"\']/', $v) ? '"' . str_replace('"', '', $v) . '"' : $v;
    }

    /** Runs a SQL script one statement at a time (respecting quoted strings), so any error is reported. */
    public static function execSql(PDO $pdo, string $sql): void
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $stmt = '';
        $inStr = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            if ($inStr) {
                $stmt .= $ch;
                if ($ch === '\\' && $i + 1 < $len) {
                    $stmt .= $sql[++$i];
                } elseif ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $stmt .= $sql[++$i];
                    } else {
                        $inStr = false;
                    }
                }
                continue;
            }
            if ($ch === "'") {
                $inStr = true;
                $stmt .= $ch;
            } elseif ($ch === ';') {
                if (trim($stmt) !== '') {
                    $pdo->exec($stmt);
                }
                $stmt = '';
            } else {
                $stmt .= $ch;
            }
        }
        if (trim($stmt) !== '') {
            $pdo->exec($stmt);
        }
    }
}
