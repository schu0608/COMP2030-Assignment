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
<!doctype html><html>
  <head>
  <meta charset="utf-8"><title>Dashboard</title>
    <link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=8">
 </head>
<body>
<h1>Welcome, <?=htmlspecialchars($user['full_name'] ?? 'Student')?></h1>
<?php if (!empty($user['profile_picture'])): ?>
  <img src="/COMP2030-Assignment/src/StudentPage/Public/uploads/<?=htmlspecialchars($user['profile_picture'])?>" alt="Profile" style="max-width:150px">
<?php endif; ?>
<p><strong>FUSSCredit Balance:</strong> <?= number_format($balance,2) ?></p>
<p>
  <a href="/COMP2030-Assignment/src/StudentPage/Public/profile_edit.php">Edit Profile</a> |
  <a href="/COMP2030-Assignment/src/StudentPage/Public/skills_manage.php">Manage Skills</a> |
  <a href="/COMP2030-Assignment/src/StudentPage/Public/transactions.php">Transactions</a> |
  <a href="/COMP2030-Assignment/src/StudentPage/Public/logout.php">Logout</a>
</p>

<p><?= nl2br(htmlspecialchars($user['bio'] ?? '')) ?></p>
</body></html>
