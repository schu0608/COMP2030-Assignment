<?php
$ROOT = dirname(__DIR__);
require_once $ROOT . '/inc/init.inc.php';
$pdo = db();
$uid = require_login();

$zones = $pdo->query("SELECT zone_id, name FROM zones ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();
  $zone = ($_POST['zone_id'] ?? '') === '' ? null : (int)$_POST['zone_id'];
  $st = $pdo->prepare("UPDATE students SET zone_id = :z WHERE student_id = :id");
  $st->execute([':z' => $zone, ':id' => $uid]);
  $msg = 'Zone updated.';
}

$me = $pdo->prepare("SELECT full_name, zone_id FROM students WHERE student_id=:id");
$me->execute([':id' => $uid]);
$me = $me->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'My Zone';
include $ROOT . '/templates/header.php';
?>
<h1>My Zone</h1>
<?php if ($msg): ?><div class="notice"><?= h($msg) ?></div><?php endif; ?>

<form method="post" class="bar" style="margin:12px 0 16px">
  <?= csrf_field() ?>
  <label class="label">Select your campus zone</label>
  <select name="zone_id">
    <option value="">— None —</option>
    <?php foreach ($zones as $z): ?>
      <option value="<?= (int)$z['zone_id'] ?>" <?= ((int)($me['zone_id'] ?? 0) === (int)$z['zone_id']) ? 'selected' : '' ?>>
        <?= h($z['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <button class="btn">Save</button>
</form>

<p class="muted">Your zone helps us suggest nearby helpers and requests.</p>

<?php include $ROOT . '/templates/footer.php'; ?>
