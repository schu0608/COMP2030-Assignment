<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/transactions.php';

require_login();
$uid = current_user_id();

$st = db()->prepare("SELECT full_name, degree, college, academic_year, bio, profile_picture FROM students WHERE student_id=?");
$st->execute([$uid]);
$user = $st->fetch();

$balance = get_fuss_balance($uid);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Dashboard</title></head><body>
<h1>Welcome, <?=htmlspecialchars($user['full_name'] ?? 'Student')?></h1>
<p><strong>FUSSCredit Balance:</strong> <?= number_format($balance,2) ?></p>
<p>
  <a href="/profile_edit.php">Edit Profile</a> |
  <a href="/skills_manage.php">Manage Skills</a> |
  <a href="/transactions.php">Transactions</a> |
  <a href="/logout.php">Logout</a>
</p>
<?php if (!empty($user['profile_picture'])): ?>
  <img src="/uploads/<?=htmlspecialchars($user['profile_picture'])?>" alt="Profile" style="max-width:150px">
<?php endif; ?>
<p><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></p>
</body></html>
