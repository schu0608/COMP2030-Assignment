<?php
require_once dirname(__DIR__) . '/inc/init.inc.php';
$uid = require_login();
$pageTitle = 'Requests';

$incomingOnly = isset($_GET['incoming']) ? 1 : 0;
$pdo = db();

$sql = "
  SELECT
    t.transaction_id, t.requester_id, t.provider_id, t.skill_id,
    t.hours AS proposed_hours,          
    t.fuss_credit_amount, t.status,
    s.name AS skill_name,
    rq.full_name AS requester_name,
    pr.full_name AS provider_name
  FROM transactions t
  ...
";

$params = [':me' => $uid];
if ($incomingOnly) $sql .= " AND t.provider_id = :me";
$sql .= " ORDER BY t.transaction_id DESC";

$rows = $pdo->prepare($sql); $rows->execute($params); $rows = $rows->fetchAll();

/* Buckets */
$open = $needsMyConfirm = $completed = [];

foreach ($rows as $r) {
  $status = (string)$r['status'];
  $mineIsRequester = ((int)$r['requester_id'] === $uid);
  $waitingForMe =
    ($mineIsRequester && $status === 'confirm_requester') ||
    (!$mineIsRequester && $status === 'confirm_provider');

  if ($status === 'confirmed') {
    $completed[] = $r;
  } elseif ($waitingForMe) {
    $needsMyConfirm[] = $r;
  } else {
    $open[] = $r; // pending / accepted / proposed / confirm_* (waiting on them)
  }
}

include dirname(__DIR__).'/templates/header.php';
?>

<h1>Your requests</h1>

<form method="get" class="bar" style="margin-bottom:12px">
  <label class="flag">
    <input type="checkbox" name="incoming" value="1" <?= $incomingOnly ? 'checked' : '' ?>>
    <span class="flag-text">Show incoming only</span>
  </label>
  <button class="btn btn--sm">Apply</button>
</form>

<?php
function who($r, $uid) {
  return ((int)$r['requester_id'] === $uid) ? 'Outgoing' : 'Incoming';
}
function otherName($r, $uid) {
  return ((int)$r['requester_id'] === $uid) ? $r['provider_name'] : $r['requester_name'];
}
function agreed_hours($r) {
  return isset($r['proposed_hours']) && $r['proposed_hours'] !== null && $r['proposed_hours'] !== ''
    ? (float)$r['proposed_hours'] : (float)$r['hours'];
}
?>

<?php if (!$open && !$needsMyConfirm && !$completed): ?>
  <p class="notice">You don’t have any requests yet. Visit <a href="/browse.php">Browse skills</a> to get started.</p>
<?php endif; ?>

<?php if ($needsMyConfirm): ?>
  <h2>Needs your confirmation</h2>
  <div class="stack">
    <?php foreach ($needsMyConfirm as $r): ?>
      <article class="card">
        <div class="grid grid--2">
          <div>
            <div class="muted"><?= who($r,$uid) ?> • waiting on you</div>
            <h3 style="margin:.2rem 0"><?= h($r['skill_name']) ?></h3>
            <div class="muted">With: <?= h(otherName($r,$uid)) ?></div>
          </div>
          <div style="text-align:right">
            <div><?= number_format(agreed_hours($r),2) ?> h • <?= number_format(agreed_hours($r),2) ?> credits</div>
            <form method="post" action="/actions/service_confirm.php" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="transaction_id" value="<?= (int)$r['transaction_id'] ?>">
              <button class="btn btn--primary btn--sm">Confirm & transfer</button>
            </form>
            <a class="btn btn--sm" href="/thread.php?id=<?= (int)$r['transaction_id'] ?>">Open thread</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($open): ?>
  <h2>Open</h2>
  <div class="stack">
    <?php foreach ($open as $r): ?>
      <article class="card">
        <div class="grid grid--2">
          <div>
            <div class="muted"><?= who($r,$uid) ?> • <?= h($r['status']) ?></div>
            <h3 style="margin:.2rem 0"><?= h($r['skill_name']) ?></h3>
            <div class="muted">With: <?= h(otherName($r,$uid)) ?></div>
          </div>
          <div style="text-align:right">
            <div><?= number_format(agreed_hours($r),2) ?> h • <?= number_format(agreed_hours($r),2) ?> credits</div>
            <a class="btn btn--sm" href="/thread.php?id=<?= (int)$r['transaction_id'] ?>">Open thread</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($completed): ?>
  <h2 id="completed">Completed</h2>
  <div class="stack">
    <?php foreach ($completed as $r): ?>
      <article class="card">
        <div class="grid grid--2">
          <div>
            <div class="muted"><?= who($r,$uid) ?> • <strong>completed</strong></div>
            <h3 style="margin:.2rem 0"><?= h($r['skill_name']) ?></h3>
            <div class="muted">With: <?= h(otherName($r,$uid)) ?></div>
          </div>
          <div style="text-align:right">
            <div><?= number_format((float)$r['fuss_credit_amount'] ?: agreed_hours($r),2) ?>
              h • <?= number_format((float)$r['fuss_credit_amount'] ?: agreed_hours($r),2) ?> credits</div>
            <a class="btn btn--sm" href="/thread.php?id=<?= (int)$r['transaction_id'] ?>">View thread</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
