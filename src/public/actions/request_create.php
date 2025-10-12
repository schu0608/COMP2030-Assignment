<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';

$uid = require_login();
validate_csrf();

$offer_id = (int)($_POST['offer_id'] ?? 0);
$hours    = (float)($_POST['hours'] ?? 0);

if ($offer_id <= 0 || $hours <= 0) {
  redirect('/browse.php?e=params');
}

/**
 * Fetch the authoritative provider + skill from the offer row.
 * This removes reliance on hidden fields and avoids mismatches.
 */
$off = db()->prepare(
  'SELECT ss.id AS offer_id,
          ss.student_id AS provider_id,
          ss.skill_id
     FROM student_skills ss
    WHERE ss.id = ? AND ss.role = "offered"'
);
$off->execute([$offer_id]);
$offer = $off->fetch();

if (!$offer) {
  redirect('/browse.php?e=offer_nf');
}

$provider_id = (int)$offer['provider_id'];
$skill_id    = (int)$offer['skill_id'];

if ($provider_id === $uid) {
  redirect('/skill.php?id='.$offer_id.'&e=self');
}

// Balance guard at request time (we’ll recheck on transfer)
$bal = (float) db()->query('SELECT fuss_credits FROM students WHERE student_id='.$uid)->fetchColumn();
if ($bal < $hours) {
  redirect('/skill.php?id='.$offer_id.'&e=credits');
}

// Create the transaction
$pdo = db();
$pdo->beginTransaction();

$ins = $pdo->prepare(
  'INSERT INTO transactions
     (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
   VALUES (?,?,?,?,?, "pending")'
);
$ins->execute([$uid, $provider_id, $skill_id, $hours, $hours]);

$tid = (int)$pdo->lastInsertId();

// Optional: first system message so there’s always something in the thread
$pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
               VALUES (?,?,?, "system")')
    ->execute([$tid, $uid, 'New request created.']);

$pdo->commit();

redirect('/thread.php?id='.$tid);