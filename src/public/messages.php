<?php
require_once __DIR__ . '/../inc/init.inc.php';

$uid = require_login();           // must be logged in
$pageTitle = 'Requests';          // shows on the right in the header
include __DIR__ . '/../templates/header.php';

/** Incoming-only toggle (requests where I am the provider) */
$incomingOnly = isset($_GET['incoming']) ? (int)$_GET['incoming'] : 0;

/** Fetch requests that involve the current user */
$sql = "
  SELECT
    sr.id                         AS req_id,
    sr.status,
    sr.requester_id,
    sr.provider_id,
    sr.requested_hours,
    sr.proposed_hours,
    COALESCE(sr.updated_at, sr.created_at) AS created_at,
    s.name                        AS skill_name,
    s.category                    AS category,
    CASE WHEN sr.requester_id = :uid THEN sr.provider_id ELSE sr.requester_id END AS other_id
  FROM service_requests sr
  JOIN skills s ON s.skill_id = sr.skill_id
  WHERE (sr.requester_id = :uid OR sr.provider_id = :uid)
    " . ($incomingOnly ? "AND sr.provider_id = :uid" : "") . "
  ORDER BY sr.id DESC
";
$stmt = db()->prepare($sql);
$stmt->execute([':uid' => $uid]);
$rows = $stmt->fetchAll();

/** Resolve counterpart names in one go */
$otherIds = array_values(array_unique(array_map(fn($r) => (int)$r['other_id'], $rows)));
$names = [];
if ($otherIds) {
  $in = implode(',', array_fill(0, count($otherIds), '?'));
  $st = db()->prepare("SELECT student_id, full_name FROM students WHERE student_id IN ($in)");
  $st->execute($otherIds);
  foreach ($st->fetchAll() as $n) $names[(int)$n['student_id']] = $n['full_name'];
}

/** Helper: status → badge class + label */
function map_status(string $status): array {
  return match ($status) {
    'pending'            => ['badge--pending',   'Pending'],
    'accepted'           => ['badge--inprogress','In progress'],
    'in_progress'        => ['badge--inprogress','In progress'],
    'confirm_provider'   => ['badge--confirm',   'Awaiting provider'],
    'confirm_requester'  => ['badge--confirm',   'Awaiting requester'],
    'complete'           => ['badge--complete',  'Complete'],
    'rejected'           => ['badge--rejected',  'Rejected'],
    default              => ['badge',            ucfirst($status ?: 'Status')],
  };
}

/** Helper: action suggestion (text + URL) based on role & status */
function next_action(array $r, int $uid): array {
  $isProvider  = ((int)$r['provider_id']  === $uid);
  $isRequester = ((int)$r['requester_id'] === $uid);
  $id = (int)$r['req_id'];

  return match ($r['status']) {
    'pending'            => $isProvider  ? ['Review request', "/thread.php?id=$id"] : ['Open', "/thread.php?id=$id"],
    'accepted','in_progress'
                          => ['Open', "/thread.php?id=$id"],
    'confirm_provider'   => $isProvider  ? ['Confirm hours', "/thread.php?id=$id#confirm"] : ['Open', "/thread.php?id=$id"],
    'confirm_requester'  => $isRequester ? ['Confirm hours', "/thread.php?id=$id#confirm"] : ['Open', "/thread.php?id=$id"],
    'complete'           => ['View', "/thread.php?id=$id"],
    'rejected'           => ['View', "/thread.php?id=$id"],
    default              => ['Open', "/thread.php?id=$id"],
  };
}
?>

<section class="container" style="margin-top:20px">
  <h1>Your requests</h1>

  <div class="filter-row">
    <form method="get" style="display:flex;align-items:center;gap:10px;margin:0">
      <label style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" class="switch" name="incoming" value="1" <?= $incomingOnly ? 'checked' : '' ?>>
        Show incoming only
      </label>
      <button class="btn btn--sm">Apply</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <div class="notice">You don’t have any requests yet. Visit <a href="/browse.php">Browse skills</a> to get started.</div>
  <?php else: ?>
    <ul class="req-list">
      <?php foreach ($rows as $r):
        $otherName = $names[(int)$r['other_id']] ?? 'Student';
        $isIncoming = ((int)$r['provider_id'] === $uid); // incoming if I'm provider
        $hours = (float)($r['proposed_hours'] ?? 0) ?: (float)($r['requested_hours'] ?? 0);
        [$badgeClass, $statusText] = map_status((string)$r['status']);
        [$actText, $actUrl] = next_action($r, $uid);
      ?>
      <li class="req-card">
        <div class="req-main">
          <div class="req-title">
            <a href="/thread.php?id=<?= (int)$r['req_id'] ?>"><?= h($r['skill_name']) ?></a>
          </div>
          <div class="req-meta">
            <?= $isIncoming ? 'from' : 'with' ?>
            <a href="/profile.php?u=<?= (int)$r['other_id'] ?>"><?= h($otherName) ?></a>
            · <?= $hours ? h(rtrim(rtrim(number_format($hours, 2), '0'), '.')) : '—' ?>h
            <?php if (!empty($r['category'])): ?> · <?= h($r['category']) ?><?php endif; ?>
          </div>
        </div>

        <div class="req-side">
          <span class="badge <?= $badgeClass ?>"><span class="dot"></span><?= h($statusText) ?></span>
          <div class="req-actions">
            <a class="btn btn--sm" href="/thread.php?id=<?= (int)$r['req_id'] ?>">Open</a>
            <a class="btn btn--sm btn--primary" href="<?= h($actUrl) ?>"><?= h($actText) ?></a>
          </div>
          <?php if (!empty($r['created_at'])): ?>
            <div class="req-time"><?= h(date('M j, Y', strtotime($r['created_at']))) ?></div>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
