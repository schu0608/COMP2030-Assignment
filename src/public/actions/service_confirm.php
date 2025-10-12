<?php
require_once dirname(__DIR__).'/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tid   = (int)($_POST['transaction_id'] ?? 0);
$override = isset($_POST['hours_override']) ? (float)$_POST['hours_override'] : null;

if ($tid <= 0) redirect('/messages.php?e=invalid');

$pdo = db();
$pdo->beginTransaction();

// Lock the row
$st = $pdo->prepare('SELECT * FROM transactions WHERE transaction_id=? FOR UPDATE');
$st->execute([$tid]);
$t = $st->fetch();
if (!$t) { $pdo->rollBack(); redirect('/messages.php?e=notfound'); }

$rid = (int)$t['requester_id'];
$pid = (int)$t['provider_id'];

if ($uid !== $rid && $uid !== $pid) {
  $pdo->rollBack(); http_response_code(403); exit('Forbidden');
}

$status = (string)$t['status'];
$terminal = ['confirmed','rejected'];
if (in_array($status, $terminal, true)) {
  $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=locked');
}

// Decide final hours (override > proposed > original)
$final = $override !== null && $override > 0 ? $override :
         ($t['proposed_hours'] !== null ? (float)$t['proposed_hours'] : (float)$t['hours']);
$final = max(0.5, $final); // basic floor

// Who is confirming now?
if ($uid === $rid) {
  if ($status === 'confirm_provider') {
    // both sides have now confirmed => transfer + close
    // check balance
    $balq = $pdo->prepare('SELECT fuss_credits FROM students WHERE student_id=? FOR UPDATE');
    $balq->execute([$rid]); $bal = (float)$balq->fetchColumn();
    if ($bal < $final) { $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=credits'); }

    // transfer
    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits - ? WHERE student_id=?')
        ->execute([$final, $rid]);
    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits + ? WHERE student_id=?')
        ->execute([$final, $pid]);

    // close transaction
    $pdo->prepare('UPDATE transactions SET status="confirmed", fuss_credit_amount=?, proposed_hours=NULL WHERE transaction_id=?')
        ->execute([$final, $tid]);

    // system message
    $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
        ->execute([$tid, $uid, 'Requester confirmed. Service completed. '.$final.' credits transferred.', 'system']);

  } else {
    // first confirmer (requester)
    $pdo->prepare('UPDATE transactions SET status="confirm_requester", proposed_hours=? WHERE transaction_id=?')
        ->execute([$final, $tid]);

    $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
        ->execute([$tid, $uid, 'Requester confirmed hours: '.number_format($final,2), 'system']);
  }
} else { // provider confirms
  if ($status === 'confirm_requester') {
    // both sides have now confirmed => transfer + close
    $balq = $pdo->prepare('SELECT fuss_credits FROM students WHERE student_id=? FOR UPDATE');
    $balq->execute([$rid]); $bal = (float)$balq->fetchColumn();
    if ($bal < $final) { $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=credits'); }

    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits - ? WHERE student_id=?')
        ->execute([$final, $rid]);
    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits + ? WHERE student_id=?')
        ->execute([$final, $pid]);

    $pdo->prepare('UPDATE transactions SET status="confirmed", fuss_credit_amount=?, proposed_hours=NULL WHERE transaction_id=?')
        ->execute([$final, $tid]);

    $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
        ->execute([$tid, $uid, 'Provider confirmed. Service completed. '.$final.' credits transferred.', 'system']);
  } else {
    // first confirmer (provider)
    $pdo->prepare('UPDATE transactions SET status="confirm_provider", proposed_hours=? WHERE transaction_id=?')
        ->execute([$final, $tid]);

    $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,?)')
        ->execute([$tid, $uid, 'Provider confirmed hours: '.number_format($final,2), 'system']);
  }
}

$pdo->commit();
redirect('/thread.php?id='.$tid);
