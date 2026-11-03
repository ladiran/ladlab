<?php
/**
 * Hardens config/.env. Run from the command line in the site folder:
 *
 *   php tools/secure_config.php                      encrypts DB_PASS and SMTP_PASS in config/.env (creates the master key if needed)
 *   php tools/secure_config.php --status             shows what is protected (never prints a secret)
 *   php tools/secure_config.php --dedicated-user [--root-user=root] [--root-pass=...]
 *                                                    creates a database user that can reach ONLY this site's database, with a long
 *                                                    random password, tests it, and switches config/.env to it (stored encrypted)
 *   php tools/secure_config.php --rotate-key         makes a new master key and re-encrypts everything that uses it
 *                                                    (.env secrets and two-step-verification secrets). Keep the printed backup path safe.
 *   php tools/secure_config.php --key-file=/path/outside/webroot/app.key
 *                                                    moves the master key to that path and records it as APP_KEY_FILE
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Command line only.');
}
require dirname(__DIR__) . '/app/bootstrap.php';

$o = getopt('', ['status', 'dedicated-user', 'root-user::', 'root-pass::', 'rotate-key', 'key-file:']);
$say = fn(string $s) => print($s . "\n");

if (isset($o['status'])) {
    $say('Database user:        ' . env('DB_USER', 'root') . (env('DB_USER', 'root') === 'root' ? '   (WARNING: root; use --dedicated-user)' : ''));
    $say('Database password:    ' . (env('DB_PASS_ENC') ? 'encrypted' : (env('DB_PASS') ? 'PLAIN TEXT (run this tool without options)' : 'empty')));
    $say('SMTP password:        ' . (env('SMTP_PASS_ENC') ? 'encrypted' : (env('SMTP_PASS') ? 'PLAIN TEXT (run this tool without options)' : 'not set')));
    $say('Master key:           ' . Crypto::keySource() . ' (' . (Crypto::keySource() === 'env' ? 'APP_KEY variable' : Crypto::keyFile()) . ')');
    exit(0);
}

if (isset($o['key-file'])) {
    $new = (string)$o['key-file'];
    $b64 = is_file(Crypto::keyFile()) ? trim((string)file_get_contents(Crypto::keyFile())) : base64_encode(Crypto::currentRawKey());
    if (!is_dir(dirname($new)) && !@mkdir(dirname($new), 0700, true)) {
        fwrite(STDERR, "Cannot create the folder for $new\n");
        exit(1);
    }
    file_put_contents($new, $b64 . "\n", LOCK_EX);
    @chmod($new, 0600);
    Crypto::updateEnvFile(['APP_KEY_FILE' => $new]);
    $say("Master key copied to $new and recorded as APP_KEY_FILE in config/.env.");
    $say('Verify the site works, then delete the old copy: ' . Crypto::defaultKeyFile());
    exit(0);
}

if (isset($o['rotate-key'])) {
    $oldRaw = Crypto::currentRawKey();
    $newRaw = random_bytes(32);
    $pairs = [];
    foreach (['DB_PASS', 'SMTP_PASS'] as $n) {
        if (env($n . '_ENC')) {
            $plain = Crypto::decrypt((string)env($n . '_ENC'), $n);
            $pairs[$n . '_ENC'] = Crypto::withKey($newRaw, fn() => Crypto::encrypt($plain, $n));
        }
    }
    $users = Db::all('SELECT id, totp_secret FROM users WHERE totp_secret IS NOT NULL');
    $reenc = [];
    foreach ($users as $u) {
        $plain = Crypto::decrypt($u['totp_secret'], 'totp');
        $reenc[(int)$u['id']] = Crypto::withKey($newRaw, fn() => Crypto::encrypt($plain, 'totp'));
    }
    $file = Crypto::keyFile();
    if (Crypto::keySource() === 'env') {
        fwrite(STDERR, "The key comes from the APP_KEY variable. Rotate it where it is set, then re-run the encrypt step. Aborting.\n");
        exit(1);
    }
    $backup = $file . '.old-' . date('Ymd-His');
    copy($file, $backup);
    @chmod($backup, 0600);
    // write the database first (transaction), then the files; if anything fails nothing is half-changed
    $pdo = Db::pdo();
    $pdo->beginTransaction();
    try {
        foreach ($reenc as $id => $enc) {
            Db::update('users', ['totp_secret' => $enc], 'id = ?', [$id]);
        }
        file_put_contents($file, base64_encode($newRaw) . "\n", LOCK_EX);
        if ($pairs) {
            Crypto::updateEnvFile($pairs);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        copy($backup, $file);
        fwrite(STDERR, 'Rotation failed and was rolled back: ' . $e->getMessage() . "\n");
        exit(1);
    }
    $say('New master key written. Re-encrypted: ' . count($pairs) . ' config secret(s), ' . count($reenc) . ' two-step secret(s).');
    $say("The previous key was saved as $backup. Once you have confirmed everything works, store it offline or delete it.");
    exit(0);
}

if (isset($o['dedicated-user'])) {
    $name = (string)env('DB_NAME', 'clearsky_academy');
    $host = (string)env('DB_HOST', '127.0.0.1');
    $port = (string)env('DB_PORT', '3306');
    $rootUser = (string)($o['root-user'] ?? 'root');
    $rootPass = (string)($o['root-pass'] ?? '');
    try {
        $root = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $rootUser, $rootPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $user = 'csa_' . substr(preg_replace('/[^a-z0-9]/', '', strtolower($name)), 0, 12) . '_' . bin2hex(random_bytes(3));
        $pass = bin2hex(random_bytes(24));
        foreach (['localhost', '127.0.0.1'] as $h) {
            $root->exec("CREATE USER '$user'@'$h' IDENTIFIED BY '$pass'");
            $root->exec('GRANT ALL PRIVILEGES ON `' . $name . "`.* TO '$user'@'$h'");
        }
        new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Could not create or test the dedicated user: ' . $e->getMessage() . "\n");
        exit(1);
    }
    Crypto::ensureKey();
    Crypto::updateEnvFile(['DB_USER' => $user, 'DB_PASS_ENC' => Crypto::encrypt($pass, 'DB_PASS')], ['DB_PASS']);
    $say("Done. The website now uses the database user $user (limited to `$name`, long random password, stored encrypted).");
    $say('The old login is no longer used by the site. If it was root with a weak or empty password, set a strong root password in your database tool.');
    exit(0);
}

// default: encrypt plain secrets
$n = AdminSecurityController::encryptConfig();
$say($n ? "Encrypted $n secret(s) in config/.env. Master key: " . Crypto::keyFile() : 'Nothing to encrypt: config/.env already holds no plain-text secrets.');
$say('Back up the key file separately from your database backups; without it the encrypted values cannot be read.');
