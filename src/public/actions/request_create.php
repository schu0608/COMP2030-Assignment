<?php
// actions/request_create.php
require_once dirname(__DIR__) . '/inc/init.inc.php';

$uid = require_login();
if (function_exists('validate_csrf')) { validate_csrf(); }

$pdo        = db();
$providerId = (int)($_POST['provider_id'] ?? 0);
$skillId    = (int)($_POST['skill_id'] ?? 0);
$offerId    = (int)($_POST['offer_id'] ?? 0); // optional, when you link to student_skills.id
$hours      = (float)($_POST['hours'] ?? 0);
$msgBody    = trim((string)($_POST['message'] ?? ''));

if ($providerId <= 0 || $skillId <= 0 || $hours <= 0) {
  redirect('/browse.php?e=bad_input');
}
if ($providerId === $uid) {
  redirect('/browse.php?e=self');
}

// 1 credit == 1 hour
$creditNeeded = $hours;

// Ensure provider + skill is actually an offered skill (avoid spoofing)
try {
  if ($offerId > 0) {
    $st = $pdo->prepare('SELECT 1 FROM student_skills WHERE id=? AND student_id=? AND skill_id=? AND role="offered"');
    $st->execute([$offerId, $providerId, $skillId]);
  } else {
    $st = $pdo->prepare('SELECT 1 FROM student_skills WHERE student_id=? AND skill_id=? AND role="offered"');
    $st->execute([$providerId, $skillId]);
  }
  if (!$st->fetchColumn()) {
    redirect('/browse.php?e=no_offer');
  }
} catch (Throwable $e) {
  redirect('/browse.php?e=server');
}

// Balance check (no negatives for requester)
$balance = (float)($pdo->query('SELECT fuss_credits FROM students WHERE student_id='.(int)$uid)->fetchColumn() ?? 0);
if ($balance < $creditNeeded) {
  redirect('/browse.php?e=credits');
}

// Prevent duplicate open transaction between same trio (basic)
$openStatuses = ['pending','accepted','proposed','confirm_requester','confirm_provider'];
$ph = implode(',', array_fill(0, count($openStatuses), '?'));
$params = array_merge([$uid, $providerId, $skillId], $openStatuses);

$dup = $pdo->prepare("SELECT transaction_id FROM transactions
                      WHERE requester_id=? AND provider_id=? AND skill_id=? AND status IN ($ph)
                      ORDER BY transaction_id DESC LIMIT 1");
$dup->execute($params);
if ($dup->fetchColumn()) {
  // already an open request; go to requests page
  redirect('/messages.php?info=exists');
}

// Create request
$pdo->beginTransaction();
try {
  $ins = $pdo->prepare('INSERT INTO transactions
    (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
    VALUES (?, ?, ?, ?, ?, "pending")');
  $ins->execute([$uid, $providerId, $skillId, $hours, $creditNeeded]);
  $tid = (int)$pdo->lastInsertId();

  // initial message (optional)
  if ($msgBody !== '') {
    $m = $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
                        VALUES (?, ?, ?, "text")');
    $m->execute([$tid, $uid, $msgBody]);
  } else {
    $m = $pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
                        VALUES (?, ?, ?, "system")');
    $m->execute([$tid, $uid, "Request created for {$hours}h"]);
  }

  $pdo->commit();

  // success → Requests page (or your thread page if you have one)
  redirect('/messages.php?created=1');
} catch (Throwable $e) {
  $pdo->rollBack();
  // fall back to browse with error; inspect server log for details
  redirect('/browse.php?e=save');
}
