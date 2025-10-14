<?php
$root = dirname(__DIR__, 2);
require_once $root . '/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tid   = (int)($_POST['transaction_id'] ?? 0);
$hours = (float)($_POST['hours'] ?? 0);

if ($tid <= 0 || $hours <= 0) redirect('/messages.php?e=invalid');

$pdo = db();
$pdo->beginTransaction();

$st = $pdo->prepare('SELECT requester_id, provider_id, status FROM transactions WHERE transaction_id=? FOR UPDATE');
$st->execute([$tid]);
$t = $st->fetch();

if (!$t) { $pdo->rollBack(); redirect('/messages.php?e=notfound'); }

if ($uid !== (int)$t['requester_id'] && $uid !== (int)$t['provider_id']) {
  $pdo->rollBack(); http_response_code(403); exit('Forbidden');
}

$terminal = ['confirmed','rejected'];
if (in_array((string)$t['status'], $terminal, true)) {
  $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=locked');
}

$upd = $pdo->prepare('UPDATE transactions SET proposed_hours=?, status="proposed" WHERE transaction_id=?');
$upd->execute([$hours, $tid]);

$msg = $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)');
$msg->execute([$tid, $uid, 'Proposed hours: '.number_format($hours,2), 'proposal']);

$pdo->commit();
redirect('/thread.php?id='.$tid);
