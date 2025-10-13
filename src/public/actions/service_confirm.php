<?php
// actions/service_confirm.php
require_once dirname(__DIR__, 2) . '/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$tx_id = (int)($_POST['tx_id'] ?? $_POST['request_id'] ?? $_POST['req_id'] ?? 0);
$hours = isset($_POST['hours']) ? (float)$_POST['hours'] : 0.0;
if ($tx_id <= 0) redirect('/messages.php?e=tx');

$pdo = db();
$pdo->beginTransaction();

try {
  // Lock the transaction row
  $st = $pdo->prepare('SELECT * FROM transactions WHERE transaction_id=? FOR UPDATE');
  $st->execute([$tx_id]);
  $tx = $st->fetch();
  if (!$tx) { $pdo->rollBack(); redirect('/messages.php?e=tx404'); }

  $rid = (int)$tx['requester_id'];
  $pid = (int)$tx['provider_id'];
  $youAreRequester = ($uid === $rid);
  $youAreProvider  = ($uid === $pid);
  if (!$youAreRequester && !$youAreProvider) { $pdo->rollBack(); redirect('/messages.php?e=auth'); }

  // Coerce hours for finalisation path
  $agreedHours = $hours > 0 ? $hours : (float)($tx['proposed_hours'] ?? $tx['hours'] ?? 0);
  if ($agreedHours <= 0) $agreedHours = (float)($tx['hours'] ?? 1.0);

  // Work out new status
  $status = $tx['status'];
  $finalising = false;

  if ($youAreRequester) {
    if ($status === 'confirm_provider') { $finalising = true; }
    $status = 'confirm_requester';
  } else { // provider
    if ($status === 'confirm_requester') { $finalising = true; }
    $status = 'confirm_provider';
  }

  // If we are finalising -> move credits and mark confirmed
  if ($finalising) {
    // Check balance of requester
    $balq = $pdo->prepare('SELECT fuss_credits FROM students WHERE student_id=? FOR UPDATE');
    $balq->execute([$rid]);
    $bal = (float)$balq->fetchColumn();

    if ($bal < $agreedHours) {
      // Not enough credits
      $pdo->rollBack();
      redirect('/thread.php?id=' . $tx_id . '&e=credits');
    }

    // Move credits
    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits - ? WHERE student_id=?')
        ->execute([$agreedHours, $rid]);
    $pdo->prepare('UPDATE students SET fuss_credits = fuss_credits + ? WHERE student_id=?')
        ->execute([$agreedHours, $pid]);

    // Mark confirmed and set monetary values
    $pdo->prepare(
      'UPDATE transactions
         SET hours=?, fuss_credit_amount=?, status="confirmed"
       WHERE transaction_id=?'
    )->execute([$agreedHours, $agreedHours, $tx_id]);

    // System note
    $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
                   VALUES (?,?,?, "system")')
        ->execute([$tx_id, $uid, 'Service completed and credits transferred.']);

    $pdo->commit();
    // Send them back with a review prompt
    redirect('/thread.php?id=' . $tx_id . '&review=1');
  }

  // Single-sided confirmation path (not final yet)
  $pdo->prepare('UPDATE transactions SET status=? WHERE transaction_id=?')
      ->execute([$status, $tx_id]);

  $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
                 VALUES (?,?,?, "system")')
      ->execute([$tx_id, $uid, $youAreRequester
        ? 'Requester confirmed completion.'
        : 'Provider confirmed completion.']);

  $pdo->commit();
  redirect('/thread.php?id=' . $tx_id);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // Optional: log error
  redirect('/thread.php?id=' . $tx_id . '&e=server');
}

// after confirming:
$stmt = $pdo->prepare("
  INSERT INTO skill_popularity (skill_id, uses, last_used)
  VALUES (:sid, 1, NOW())
  ON DUPLICATE KEY UPDATE uses = uses + 1, last_used = NOW()
");
$stmt->execute([':sid' => $skillId]);
