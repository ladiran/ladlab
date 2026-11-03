<?php
declare(strict_types=1);

/* One-time web installer. It locks itself after a successful install. Delete this file when you go live. */
define('ROOT', dirname(__DIR__));
require ROOT . '/app/Installer.php';

session_name('csa_install');
session_start();
$h = fn(?string $s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$self = strtok($_SERVER['REQUEST_URI'] ?? 'install.php', '?');

$locked = is_file(ROOT . '/storage/install.lock');
$problems = $locked ? [] : Installer::problems();
$errors = [];
$done = null;
$v = [
    'host' => '127.0.0.1', 'port' => '3306', 'name' => 'clearsky_academy', 'user' => 'root', 'pass' => '',
    'site_name' => 'ClearSky Academy', 'admin_name' => '', 'admin_email' => '', 'app_url' => '', 'sample' => '1', 'env' => 'local', 'app_user' => '1',
];

if (!$locked && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (!$problems) {
        foreach (['host', 'port', 'name', 'user', 'pass', 'site_name', 'admin_name', 'admin_email', 'app_url'] as $k) {
            $v[$k] = trim((string)($_POST[$k] ?? ''));
        }
        $v['pass'] = (string)($_POST['pass'] ?? '');
        $v['sample'] = isset($_POST['sample']) ? '1' : '0';
        $v['app_user'] = isset($_POST['app_user']) ? '1' : '0';
        $v['env'] = ($_POST['env'] ?? 'local') === 'production' ? 'production' : 'local';
        $password = (string)($_POST['admin_password'] ?? '');
        if ($password !== (string)($_POST['admin_password2'] ?? '')) {
            $errors[] = 'The two passwords do not match.';
        } else {
            try {
                $done = Installer::run([
                    'host' => $v['host'], 'port' => (int)$v['port'], 'name' => $v['name'], 'user' => $v['user'], 'pass' => $v['pass'],
                    'site_name' => $v['site_name'], 'app_url' => $v['app_url'], 'admin_name' => $v['admin_name'], 'admin_email' => $v['admin_email'],
                    'admin_password' => $password, 'sample' => $v['sample'] === '1', 'env' => $v['env'], 'app_user' => $v['app_user'] === '1',
                ]);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}
$_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(24));
$base = rtrim(dirname($self), '/');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Install | ClearSky Academy</title>
<style>
body{font-family:"Segoe UI",system-ui,sans-serif;background:#eef3f9;color:#1a2433;margin:0;padding:24px;line-height:1.55}
.box{max-width:640px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;box-shadow:0 4px 20px rgba(11,31,58,.12)}
h1{font-family:Georgia,serif;color:#0b1f3a;margin-top:0}
label{display:block;font-weight:600;font-size:.9rem;margin:14px 0 4px}
input[type=text],input[type=email],input[type=password],input[type=number],select{width:100%;padding:9px 11px;border:1px solid #c4cfdd;border-radius:7px;font:inherit}
.row{display:grid;grid-template-columns:2fr 1fr;gap:12px}
button{background:#f5a623;border:0;border-radius:8px;padding:12px 24px;font:600 1rem inherit;cursor:pointer;margin-top:20px}
.err{background:#fdeeee;border:1px solid #f3b9b9;color:#8c2222;padding:10px 14px;border-radius:8px;margin:10px 0}
.ok{background:#e8f6ef;border:1px solid #a8dcc2;color:#14603f;padding:10px 14px;border-radius:8px;margin:10px 0}
.warn{background:#fff3d6;border:1px solid #e0b24a;color:#6b4800;padding:10px 14px;border-radius:8px;margin:10px 0}
small{color:#5b6b82}h2{font-size:1.05rem;color:#0b1f3a;margin:26px 0 0;border-top:1px solid #dde5ef;padding-top:16px}
</style></head><body><div class="box">
<h1>ClearSky Academy: installation</h1>
<?php if ($locked): ?>
  <div class="warn">The site is already installed, so this installer is locked. For safety, delete <code>public/install.php</code>.</div>
  <p><a href="<?= $h($base) ?>/admin/login">Go to the admin sign-in</a> · <a href="<?= $h($base) ?>/">Open the website</a></p>
<?php elseif ($done): ?>
  <div class="ok"><strong>Installation complete.</strong><ul><?php foreach ($done as $l): ?><li><?= $h($l) ?></li><?php endforeach; ?></ul></div>
  <div class="warn"><strong>Next:</strong> delete <code>public/install.php</code> now (the installer is already locked, but there is no reason to keep it).</div>
  <p><a href="<?= $h($base) ?>/admin/login"><strong>Sign in to the admin area →</strong></a><br><a href="<?= $h($base) ?>/">View the website</a></p>
<?php else: ?>
  <p>This sets up the database and your administrator account. It takes under a minute.</p>
  <?php foreach ($problems as $p): ?><div class="err"><?= $h($p) ?></div><?php endforeach; ?>
  <?php foreach (Installer::notices() as $n): ?><div class="warn"><?= $h($n) ?></div><?php endforeach; ?>
  <?php foreach ($errors as $e): ?><div class="err"><?= $h($e) ?></div><?php endforeach; ?>
  <?php if (!$problems): ?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= $h($_SESSION['csrf']) ?>">
    <h2>1. Database (WAMP defaults are filled in)</h2>
    <div class="row"><div><label>MySQL host<input type="text" name="host" value="<?= $h($v['host']) ?>" required></label></div><div><label>Port<input type="number" name="port" value="<?= $h($v['port']) ?>" required></label></div></div>
    <label>Database name <small>(created if it does not exist)</small><input type="text" name="name" value="<?= $h($v['name']) ?>" required pattern="[A-Za-z0-9_]+"></label>
    <div class="row"><div><label>MySQL user<input type="text" name="user" value="<?= $h($v['user']) ?>" required></label></div><div><label>Password<input type="password" name="pass" autocomplete="off"></label></div></div>
    <small>WAMP usually uses user <b>root</b> with an empty password.</small>
    <label><input type="checkbox" name="app_user" value="1" <?= $v['app_user'] === '1' ? 'checked' : '' ?>> <b>Create a separate, limited database user for the website (strongly recommended).</b><br><small>The site then runs with a long random password that can open only this database, stored encrypted. The login typed above is used once to set up and is not saved.</small></label>

    <h2>2. Website</h2>
    <label>Site name<input type="text" name="site_name" value="<?= $h($v['site_name']) ?>" required></label>
    <label><input type="checkbox" name="sample" value="1" <?= $v['sample'] === '1' ? 'checked' : '' ?>> Load sample content (recommended for testing: placeholder publications, posts, etc.)</label>
    <label>Public web address <small>(live site only, e.g. https://yourdomain.com; leave empty for testing. Needed so Google sees the right address)</small><input type="text" name="app_url" value="<?= $h($v['app_url']) ?>" placeholder="https://yourdomain.com"></label>
    <label>Environment<select name="env"><option value="local" <?= $v['env'] === 'local' ? 'selected' : '' ?>>Local testing (shows error details)</option><option value="production" <?= $v['env'] === 'production' ? 'selected' : '' ?>>Live website (hides error details)</option></select></label>

    <h2>3. Administrator account</h2>
    <label>Your name<input type="text" name="admin_name" value="<?= $h($v['admin_name']) ?>" required></label>
    <label>Email (you sign in with this)<input type="email" name="admin_email" value="<?= $h($v['admin_email']) ?>" required></label>
    <label>Password <small>(at least 10 characters)</small><input type="password" name="admin_password" minlength="10" required autocomplete="new-password"></label>
    <label>Repeat password<input type="password" name="admin_password2" minlength="10" required autocomplete="new-password"></label>
    <button type="submit">Install</button>
  </form>
  <?php endif; ?>
<?php endif; ?>
</div></body></html>
