<?php
// actions/review_submit.php
require_once dirname(__DIR__, 2) . '/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tx_id   = (int)($_POST['tx_id'] ?? 0);
$stars   = (int)($_POST['stars'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($tx_id <= 0 || $stars < 1 || $stars > 5) {
  redirect('/messages.php?e=review');
}

$pdo = db();

$st = $pdo->prepare('SELECT * FROM transactions WHERE transaction_id=?');
$st->execute([$tx_id]);
$tx = $st->fetch();
if (!$tx) redirect('/messages.php?e=tx404');

// Must be a participant and transaction must be confirmed
$rid = (int)$tx['requester_id'];
$pid = (int)$tx['provider_id'];
if ($tx['status'] !== 'confirmed') redirect('/thread.php?id='.$tx_id);

if ($uid !== $rid && $uid !== $pid) redirect('/thread.php?id='.$tx_id.'&e=auth');

$reviewee = ($uid === $rid) ? $pid : $rid;

// Store or update review (unique per reviewer+transaction)
$pdo->prepare(
  'INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, stars, comment)
   VALUES (?,?,?,?,?)
   ON DUPLICATE KEY UPDATE stars=VALUES(stars), comment=VALUES(comment)'
)->execute([$tx_id, $uid, $reviewee, $stars, $comment]);

// Optional: drop a system message into the thread
$pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
               VALUES (?,?,?, "system")')
    ->execute([$tx_id, $uid, 'Left a review ('.$stars.'★).']);

redirect('/thread.php?id='.$tx_id.'&reviewed=1#review');
