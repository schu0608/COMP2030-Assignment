<?php
// src/public/actions/request_create.php
//
// Creates a new service request (transaction) from an offer.
// Expects: POST { offer_id, hours } and a valid CSRF token.

// 1) Correct include path: from /public/actions -> /src/inc/...
$root = dirname(__DIR__, 2);          // -> /src
require_once $root . '/inc/init.inc.php';

$uid = require_login();               // must be logged in
validate_csrf();

// ------------------------------------------------------------------
// Input
// ------------------------------------------------------------------
$offer_id = (int)($_POST['offer_id'] ?? 0);
$hoursRaw = trim((string)($_POST['hours'] ?? '0'));
$hours    = (float)$hoursRaw;

// Validate hours: 0.5 .. 10.0 in 0.5 increments
$validHalfHours = function (float $h): bool {
    if ($h < 0.5 || $h > 10.0) return false;
    // check 0.5 steps (multiply by 2 -> should be whole)
    return abs(($h * 2) - round($h * 2)) < 1e-6;
};
if ($offer_id <= 0 || !$validHalfHours($hours)) {
    redirect('/browse.php?e=bad_input');
}

// ------------------------------------------------------------------
// Resolve the offer → provider + skill (must exist & be "offered")
// ------------------------------------------------------------------
$pdo = db();

$st = $pdo->prepare(
    'SELECT ss.id          AS offer_id,
            ss.student_id  AS provider_id,
            s.skill_id     AS skill_id,
            st.full_name   AS provider_name
       FROM student_skills ss
       JOIN skills    s  ON s.skill_id     = ss.skill_id
       JOIN students  st ON st.student_id  = ss.student_id
      WHERE ss.id = ? AND ss.role = "offered"
      LIMIT 1'
);
$st->execute([$offer_id]);
$offer = $st->fetch();

if (!$offer) {
    redirect('/browse.php?e=not_found');
}

$provider_id = (int)$offer['provider_id'];
$skill_id    = (int)$offer['skill_id'];

// No self-requests
if ($provider_id === $uid) {
    redirect('/skill.php?id=' . $offer_id . '&e=self');
}

// ------------------------------------------------------------------
// Requester balance check (cannot create if not enough credits)
// ------------------------------------------------------------------
$bal = (float)$pdo->query('SELECT fuss_credits FROM students WHERE student_id=' . (int)$uid)->fetchColumn();
if ($bal < $hours) {
    redirect('/skill.php?id=' . $offer_id . '&e=credits');
}

// ------------------------------------------------------------------
// Deduplicate: if there’s already an open transaction for this pair
// and skill, bounce the user to that thread instead of creating a new one.
// ------------------------------------------------------------------
$dup = $pdo->prepare(
    'SELECT transaction_id
       FROM transactions
      WHERE requester_id = ?
        AND provider_id  = ?
        AND skill_id     = ?
        AND status IN ("pending","accepted","proposed","confirm_requester","confirm_provider")
      ORDER BY transaction_id DESC
      LIMIT 1'
);
$dup->execute([$uid, $provider_id, $skill_id]);
$open = $dup->fetch();

if ($open) {
    redirect('/skill.php?id=' . $offer_id . '&ok=sent&tid=' . (int)$open['transaction_id']);
}

// ------------------------------------------------------------------
// Create transaction + seed conversation (atomic)
// ------------------------------------------------------------------
try {
    $pdo->beginTransaction();

    // Fuss credits moved later upon double confirmation; we still record
    // the intended hours & fuss_credit_amount now (equal to hours).
    $ins = $pdo->prepare(
        'INSERT INTO transactions
            (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
         VALUES (?,?,?,?,?, "pending")'
    );
    $ins->execute([$uid, $provider_id, $skill_id, $hours, $hours]);
    $tid = (int)$pdo->lastInsertId();

    // Seed system message (helps the thread read naturally)
    $sys = $pdo->prepare(
        'INSERT INTO messages (transaction_id, sender_id, body, type)
         VALUES (?,?,?, "system")'
    );
    $sys->execute([$tid, $uid, 'Requested service']);

    // Optional: a human message so the provider sees something in the thread
    $txt = $pdo->prepare(
        'INSERT INTO messages (transaction_id, sender_id, body, type)
         VALUES (?,?,?, "text")'
    );
    $txt->execute([$tid, $uid, "Hi! I'd like to request this service."]);

    $pdo->commit();

    // Back to the skill page with a success banner + deep link to the thread
    redirect('/skill.php?id=' . $offer_id . '&ok=sent&tid=' . $tid);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // You can log $e->getMessage() if you have logging
    redirect('/skill.php?id=' . $offer_id . '&e=server');
}
