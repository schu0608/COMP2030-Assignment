<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';

$uid   = require_login();
validate_csrf();

$request_id = (int)($_POST['request_id'] ?? 0);
$hours      = (float)($_POST['hours'] ?? 0);
if ($request_id <= 0 || $hours <= 0) {
  redirect('/browse.php?e=params');
}

/* Resolve requester + skill from the requested row */
$st = db()->prepare('SELECT ss.student_id AS requester_id, ss.skill_id
                       FROM student_skills ss
                      WHERE ss.id = ? AND ss.role = "requested"');
$st->execute([$request_id]);
$row = $st->fetch();

if (!$row) redirect('/browse.php?e=req_nf');

$requester_id = (int)$row['requester_id'];
$skill_id     = (int)$row['skill_id'];

if ($requester_id === $uid) {
  redirect('/browse.php?e=self'); // can’t offer to yourself
}

/* Prevent duplicate open threads between same requester/provider/skill */
$chk = db()->prepare('SELECT transaction_id
                        FROM transactions
                       WHERE requester_id=? AND provider_id=? AND skill_id=?
                         AND status IN ("pending","accepted","proposed","confirm_requester","confirm_provider","in_progress")
                    ORDER BY transaction_id DESC LIMIT 1');
$chk->execute([$requester_id, $uid, $skill_id]);
$existing = $chk->fetchColumn();
if ($existing) {
  redirect('/thread.php?id='.$existing);
}

/* Create transaction (pending) + seed a system message */
$pdo = db();
$pdo->beginTransaction();

$ins = $pdo->prepare('INSERT INTO transactions
  (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
  VALUES (?,?,?,?,?, "pending")');
$ins->execute([$requester_id, $uid, $skill_id, $hours, $hours]);

$tid = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO messages (transaction_id, sender_id, body, type)
               VALUES (?,?,?, "system")')
    ->execute([$tid, $uid, 'Offered to help.']);

$pdo->commit();

redirect('/thread.php?id='.$tid);
