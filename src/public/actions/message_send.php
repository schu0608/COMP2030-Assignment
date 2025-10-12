<?php
$root = dirname(__DIR__, 2);
require_once $root . '/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tid  = (int)($_POST['transaction_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($tid <= 0 || $body === '') redirect('/messages.php?e=invalid');

// Check membership + status
$st = db()->prepare('SELECT requester_id, provider_id, status FROM transactions WHERE transaction_id=?');
$st->execute([$tid]);
$t = $st->fetch();

if (!$t) redirect('/messages.php?e=notfound');

if ($uid !== (int)$t['requester_id'] && $uid !== (int)$t['provider_id']) {
  http_response_code(403); exit('Forbidden');
}

$terminal = ['confirmed','rejected'];
if (in_array((string)$t['status'], $terminal, true)) {
  redirect('/thread.php?id='.$tid.'&e=locked');
}

$ins = db()->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)');
$ins->execute([$tid, $uid, $body, 'text']);

redirect('/thread.php?id='.$tid);
