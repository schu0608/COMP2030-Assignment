<?php
$ROOT = dirname(__DIR__, 2);
require_once $ROOT . '/inc/init.inc.php';

validate_csrf();
$uid   = require_login();
$to_id = (int)($_POST['to_id'] ?? 0);
$body  = trim((string)($_POST['body'] ?? ''));

if ($to_id <= 0 || $to_id === $uid || $body === '') {
  redirect('/pm/new.php');
}

$pdo = db();
$pdo->beginTransaction();

try {
  $ok = $pdo->prepare("SELECT 1 FROM students WHERE student_id=? AND active=1");
  $ok->execute([$to_id]);
  if (!$ok->fetchColumn()) {
    $pdo->rollBack();
    redirect('/pm/new.php');
  }

  $ins = $pdo->prepare(
    "INSERT INTO transactions (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
     VALUES (?, ?, 0, 0, 0, 'pending')"
  );
  $ins->execute([$uid, $to_id]);
  $tid = (int)$pdo->lastInsertId();

  $msg = $pdo->prepare(
    "INSERT INTO messages (transaction_id, sender_id, body, type)
     VALUES (?, ?, ?, 'text')"
  );
  $msg->execute([$tid, $uid, $body]);

  $pdo->commit();
  redirect("/thread.php?id={$tid}");
} catch (Throwable $e) {
  $pdo->rollBack();
  redirect('/pm/new.php');
}
