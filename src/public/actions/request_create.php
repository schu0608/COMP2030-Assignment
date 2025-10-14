<?php
$root = dirname(__DIR__, 2);        
require_once $root . '/inc/init.inc.php';

$uid = require_login();             
validate_csrf();

$offer_id = (int)($_POST['offer_id'] ?? 0);
$hoursRaw = trim((string)($_POST['hours'] ?? '0'));
$hours    = (float)$hoursRaw;

$validHalfHours = function (float $h): bool {
    if ($h < 0.5 || $h > 10.0) return false;
    return abs(($h * 2) - round($h * 2)) < 1e-6;
};
if ($offer_id <= 0 || !$validHalfHours($hours)) {
    redirect('/browse.php?e=bad_input');
}
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

if ($provider_id === $uid) {
    redirect('/skill.php?id=' . $offer_id . '&e=self');
}

$bal = (float)$pdo->query('SELECT fuss_credits FROM students WHERE student_id=' . (int)$uid)->fetchColumn();
if ($bal < $hours) {
    redirect('/skill.php?id=' . $offer_id . '&e=credits');
}

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

try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare(
        'INSERT INTO transactions
            (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
         VALUES (?,?,?,?,?, "pending")'
    );
    $ins->execute([$uid, $provider_id, $skill_id, $hours, $hours]);
    $tid = (int)$pdo->lastInsertId();

    $sys = $pdo->prepare(
        'INSERT INTO messages (transaction_id, sender_id, body, type)
         VALUES (?,?,?, "system")'
    );
    $sys->execute([$tid, $uid, 'Requested service']);

    $txt = $pdo->prepare(
        'INSERT INTO messages (transaction_id, sender_id, body, type)
         VALUES (?,?,?, "text")'
    );
    $txt->execute([$tid, $uid, "Hi! I'd like to request this service."]);

    $pdo->commit();

    redirect('/skill.php?id=' . $offer_id . '&ok=sent&tid=' . $tid);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    redirect('/skill.php?id=' . $offer_id . '&e=server');
}
