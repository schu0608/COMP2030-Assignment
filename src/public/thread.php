<?php
require_once dirname(__DIR__).'/inc/init.inc.php';

$uid = require_login();
$tid = (int)($_GET['id'] ?? 0);

$pageTitle = 'Request';

// Load transaction + people + skill
$sql = <<<SQL
SELECT
  t.transaction_id, t.requester_id, t.provider_id, t.skill_id,
  t.hours, t.proposed_hours, t.fuss_credit_amount, t.status,
  r.full_name AS requester_name,
  p.full_name AS provider_name,
  s.name      AS skill_name,
  s.category  AS skill_category
FROM transactions t
JOIN students r ON r.student_id = t.requester_id
JOIN students p ON p.student_id = t.provider_id
JOIN skills   s ON s.skill_id   = t.skill_id
WHERE t.transaction_id = ?
SQL;

$st = db()->prepare($sql);
$st->execute([$tid]);
$t = $st->fetch();

if (!$t) { http_response_code(404); echo 'Thread not found'; exit; }

// Access gate – only parties can view
if ($uid !== (int)$t['requester_id'] && $uid !== (int)$t['provider_id']) {
  http_response_code(403); echo 'Forbidden'; exit;
}

$isRequester = $uid === (int)$t['requester_id'];
$isProvider  = $uid === (int)$t['provider_id'];

$terminalStatuses = ['confirmed','rejected'];
$isLocked = in_array((string)$t['status'], $terminalStatuses, true);

// Pick a display “final hours”
$finalHours =
  $t['fuss_credit_amount'] !== null ? (float)$t['fuss_credit_amount'] :
  ($t['proposed_hours']    !== null ? (float)$t['proposed_hours']    :
                                      (float)$t['hours']);

// messages
$m = db()->prepare(
  'SELECT m.id, m.sender_id, s.full_name, m.body, m.type, m.created_at
     FROM messages m
     JOIN students s ON s.student_id = m.sender_id
    WHERE m.transaction_id = ?
 ORDER BY m.created_at ASC, m.id ASC'
);
$m->execute([$tid]);
$messages = $m->fetchAll();

include dirname(__DIR__).'/templates/header.php';
?>
<h1><?= h($t['skill_name']) ?></h1>

<p class="muted">
  Status: <strong><?= h($t['status']) ?></strong>
  • Hours: <?= number_format((float)$t['hours'],2) ?>
  • Credits: <?= number_format((float)($t['fuss_credit_amount'] ?? $t['proposed_hours'] ?? $t['hours']),2) ?>
</p>

<?php if ($isLocked): ?>
  <div class="notice">
    This request is <strong>completed</strong> and the thread is now read-only.
  </div>
<?php endif; ?>

<h2>Conversation</h2>
<ul class="list" style="margin:0 0 12px 18px">
  <?php foreach ($messages as $msg): ?>
    <li>
      <strong><?= h($msg['full_name']) ?></strong>:
      <?= nl2br(h($msg['body'])) ?>
      <span class="muted">(<?= h($msg['type']) ?>)</span>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (!$isLocked): ?>
<form method="post" action="/actions/message_send.php" class="stack">
  <?= csrf_field() ?>
  <input type="hidden" name="transaction_id" value="<?= (int)$t['transaction_id'] ?>">
  <textarea name="body" placeholder="Write a message..." required></textarea>
  <button class="btn">Send</button>
</form>
<?php endif; ?>

<section class="stack" style="margin-top:22px">
  <h2>Propose alternative</h2>
  <?php if ($isLocked): ?>
    <div class="muted">Propose alternative (locked)</div>
  <?php else: ?>
    <form method="post" action="/actions/propose.php" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="transaction_id" value="<?= (int)$t['transaction_id'] ?>">
      <label>Hours
        <input type="number" name="hours" min="0.5" step="0.5"
               value="<?= number_format($finalHours,2,'.','') ?>" required>
      </label>
      <button class="btn">Propose</button>
    </form>
  <?php endif; ?>
</section>

<section class="stack" style="margin-top:22px">
  <h2>Confirm completion</h2>
  <?php if ($isLocked): ?>
    <label>Final hours
      <input value="<?= number_format($finalHours,2) ?>" disabled>
    </label>
    <button class="btn" disabled>Confirmed</button>
  <?php else: ?>
    <form method="post" action="/actions/service_confirm.php" class="stack">
      <?= csrf_field() ?>
      <input type="hidden" name="transaction_id" value="<?= (int)$t['transaction_id'] ?>">
      <label>Final hours
        <input name="hours_override" type="number" step="0.5" min="0.5"
               value="<?= number_format($finalHours,2,'.','') ?>">
      </label>
      <button class="btn btn--primary">Confirm</button>
    </form>
  <?php endif; ?>
</section>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
