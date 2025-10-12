<?php
// src/public/messages.php
require_once dirname(__DIR__).'/inc/init.inc.php';

$uid = require_login();
$pdo = db();

$incomingOnly = isset($_GET['incoming']) && $_GET['incoming'] === '1';

$pageTitle = 'Requests';

/**
 * Fetch transactions for this user by status bucket.
 *
 * @param array $statuses list of status strings
 * @return array
 */
function fetch_requests(PDO $pdo, int $uid, array $statuses, bool $incomingOnly): array {
  if (!$statuses) return [];

  // IN (?, ?, ?) list for statuses
  $in = implode(',', array_fill(0, count($statuses), '?'));

  $sql = "
    SELECT
      t.transaction_id, t.requester_id, t.provider_id,
      t.skill_id, t.hours, t.fuss_credit_amount, t.status,
      s.name AS skill_name,
      r.full_name AS requester_name, r.student_id AS requester_id,
      p.full_name AS provider_name, p.student_id AS provider_id,
      CASE WHEN t.requester_id = ? THEN 'outgoing' ELSE 'incoming' END AS direction,
      CASE WHEN t.requester_id = ? THEN p.full_name ELSE r.full_name END AS partner_name,
      CASE WHEN t.requester_id = ? THEN p.student_id ELSE r.student_id END AS partner_id
    FROM transactions t
    JOIN skills   s ON s.skill_id = t.skill_id
    JOIN students r ON r.student_id = t.requester_id
    JOIN students p ON p.student_id = t.provider_id
    WHERE (t.requester_id = ? OR t.provider_id = ?)
  ";

  $params = [$uid, $uid, $uid, $uid, $uid];

  if ($incomingOnly) {
    $sql .= " AND t.provider_id = ? ";
    $params[] = $uid;
  }

  $sql .= " AND t.status IN ($in)
            ORDER BY t.transaction_id DESC";

  $params = array_merge($params, $statuses);

  $st = $pdo->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

// Buckets
$OPEN_STATUSES = ['pending','accepted','proposed','confirm_requester','confirm_provider'];
$DONE_STATUSES = ['confirmed'];

$openRows = fetch_requests($pdo, $uid, $OPEN_STATUSES, $incomingOnly);
$doneRows = fetch_requests($pdo, $uid, $DONE_STATUSES, $incomingOnly);

include dirname(__DIR__).'/templates/header.php';
?>

<h1>Your requests</h1>

<form class="bar" method="get" style="margin-bottom: 10px">
  <label class="pill-switch" style="margin-right:8px">
    <input type="hidden" name="incoming" value="0">
    <input type="checkbox" id="showIncoming" name="incoming" value="1" <?= $incomingOnly ? 'checked' : '' ?> style="margin-right:8px">
    <span class="label">Show incoming only</span>
  </label>
  <button class="btn btn--pill btn--sm">Apply</button>
</form>

<?php
// Helper to render one request card
function req_card(array $r): void {
  $dir  = $r['direction']; // incoming/outgoing
  $with = $r['partner_name'];
  $withId = (int)$r['partner_id'];

  // status badge colour choices (map to utility classes already in style.css)
  $status = (string)$r['status'];
  $badgeClass = match ($status) {
    'pending'            => 'badge badge--warn',
    'accepted','proposed'=> 'badge',
    'confirm_requester',
    'confirm_provider'   => 'badge badge--info',
    'confirmed'          => 'badge badge--ok',
    default              => 'badge',
  };
  ?>
  <article class="card request-card">
    <div class="request-card__head">
      <div class="request-card__titles">
        <h3 class="request-card__title"><?= h($r['skill_name']) ?></h3>
        <div class="request-card__meta">
          <span class="pill"><?= h($dir) ?></span>
          <span class="<?= $badgeClass ?>"><?= h($status) ?></span>
        </div>
      </div>
      <div class="request-card__cta">
        <a class="btn btn--primary" href="/thread.php?id=<?= (int)$r['transaction_id'] ?>">Open thread</a>
      </div>
    </div>

    <div class="request-card__row">
      <div class="muted">With:</div>
      <a class="link" href="/profile.php?u=<?= $withId ?>"><?= h($with) ?></a>
    </div>

    <div class="request-card__row">
      <div class="muted">Details:</div>
      <div><?= number_format((float)$r['hours'],2) ?> h • <?= number_format((float)$r['fuss_credit_amount'],2) ?> credits</div>
    </div>
  </article>
  <?php
}
?>

<?php if (!$openRows && !$doneRows): ?>
  <div class="notice">
    You don’t have any requests yet. Visit
    <a class="link" href="/browse.php">Browse skills</a>
    to get started.
  </div>
<?php endif; ?>

<?php if ($openRows): ?>
  <h2 style="margin-top: 18px">Open</h2>
  <div class="card-grid">
    <?php foreach ($openRows as $r) { req_card($r); } ?>
  </div>
<?php endif; ?>

<?php if ($doneRows): ?>
  <h2 style="margin-top: 24px">Completed</h2>
  <div class="card-grid">
    <?php foreach ($doneRows as $r) { req_card($r); } ?>
  </div>
<?php endif; ?>

<style>
  /* Small component styling to complement your style.css */
  .request-card__head {
    display:flex; align-items:center; justify-content:space-between; gap: 12px;
    margin-bottom: 10px;
  }
  .request-card__titles { display:flex; flex-direction:column; gap:4px; }
  .request-card__title { margin:0; font-size:1.05rem; font-weight:800; }
  .request-card__meta { display:flex; gap:8px; align-items:center; }
  .request-card__cta { white-space:nowrap; }
  .request-card__row {
    display:grid; grid-template-columns: 80px 1fr; gap: 10px;
    align-items:center; margin-top:8px;
  }
  .pill {
    background: var(--muted-bg); border:1px solid var(--border);
    border-radius:999px; padding:2px 10px; font-size:.85rem; color: var(--text);
  }
  .badge {
    background: var(--muted-bg); border:1px solid var(--border);
    border-radius:8px; padding:2px 8px; font-size:.85rem; color: var(--text);
  }
  .badge--warn { color: #f59e0b; border-color: color-mix(in srgb, var(--border) 60%, #f59e0b 40%); }
  .badge--info { color: #60a5fa; border-color: color-mix(in srgb, var(--border) 60%, #60a5fa 40%); }
  .badge--ok   { color: #22c55e; border-color: color-mix(in srgb, var(--border) 60%, #22c55e 40%); }
  .link { color: var(--accent); text-decoration: none; }
  .link:hover { text-decoration: underline; }
</style>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
