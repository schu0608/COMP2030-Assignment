<?php
$root = dirname(__DIR__, 2);
require_once $root . '/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tid    = (int)($_POST['transaction_id'] ?? 0);
$action = ($_POST['action'] ?? '');

$allowed = ['accept','reject'];
if ($tid <= 0 || !in_array($action, $allowed, true)) redirect('/messages.php?e=invalid');

$pdo = db();
$pdo->beginTransaction();

$st = $pdo->prepare('SELECT requester_id, provider_id, status FROM transactions WHERE transaction_id=? FOR UPDATE');
$st->execute([$tid]); $t = $st->fetch();

if (!$t) { $pdo->rollBack(); redirect('/messages.php?e=notfound'); }
if ($uid !== (int)$t['provider_id'] && $uid !== (int)$t['requester_id']) {
  $pdo->rollBack(); http_response_code(403); exit('Forbidden');
}

$terminal = ['confirmed','rejected'];
if (in_array((string)$t['status'], $terminal, true)) {
  $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=locked');
}

if ($action === 'reject') {
  $pdo->prepare('UPDATE transactions SET status="rejected" WHERE transaction_id=?')->execute([$tid]);
  $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
      ->execute([$tid, $uid, 'Request rejected.', 'system']);
} else {
  $pdo->prepare('UPDATE transactions SET status="accepted" WHERE transaction_id=?')->execute([$tid]);
  $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
      ->execute([$tid, $uid, 'Provider accepted.', 'system']);
}

$pdo->commit();
redirect('/thread.php?id='.$tid);
