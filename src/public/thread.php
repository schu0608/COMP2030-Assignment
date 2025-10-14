<?php
$ROOT = dirname(__DIR__);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$pdo = db();  


function go(string $url): void {
  if (function_exists('redirect')) { redirect($url); }
  header("Location: $url");
  exit;
}

function find_open_tx(PDO $pdo, int $me, int $provider, int $skill): ?int {
  $st = $pdo->prepare("
    SELECT transaction_id
    FROM transactions
    WHERE requester_id = :me
      AND provider_id  = :prov
      AND skill_id     = :skill
      AND status IN ('pending','accepted','proposed','confirm_requester','confirm_provider')
    ORDER BY transaction_id DESC
    LIMIT 1
  ");
  $st->execute([':me'=>$me, ':prov'=>$provider, ':skill'=>$skill]);
  $id = $st->fetchColumn();
  return $id ? (int)$id : null;
}

function create_tx(PDO $pdo, int $me, int $provider, int $skill): int {
  $hours = 1.0;
  $st = $pdo->prepare("
    INSERT INTO transactions (requester_id, provider_id, skill_id, hours, fuss_credit_amount, status)
    VALUES (:me, :prov, :skill, :hrs, :hrs, 'pending')
  ");
  $st->execute([':me'=>$me, ':prov'=>$provider, ':skill'=>$skill, ':hrs'=>$hours]);
  return (int)$pdo->lastInsertId();
}

$tid = (int)($_GET['id'] ?? 0);
if ($tid <= 0) {
  $provider = (int)($_GET['provider'] ?? 0);
  $skill    = (int)($_GET['skill'] ?? 0);

  if ($provider && $skill) {
    if ($provider === $uid) {
      http_response_code(400); exit('Cannot start a thread with yourself.');
    }
    $existing = find_open_tx($pdo, $uid, $provider, $skill);
    if ($existing) {
      go("/thread.php?id=".$existing);
    }
    $newId = create_tx($pdo, $uid, $provider, $skill);
    go("/thread.php?id=".$newId);
  }

  http_response_code(404); exit('Thread not found');
}

function db_has_column(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare(
    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?"
  );
  $q->execute([$table, $col]);
  return (bool)$q->fetchColumn();
}
function db_has_table(PDO $pdo, string $table): bool {
  $q = $pdo->prepare(
    "SELECT 1 FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?"
  );
  $q->execute([$table]);
  return (bool)$q->fetchColumn();
}

$hasProp      = db_has_column($pdo, 'transactions', 'proposed_hours');
$hasReviewsT  = db_has_table($pdo, 'reviews');
$hasReadsT    = db_has_table($pdo, 'message_reads');

$fieldList = "t.transaction_id,t.requester_id,t.provider_id,t.skill_id,
              t.hours,t.fuss_credit_amount,t.status";
if ($hasProp) { $fieldList .= ",t.proposed_hours"; }

$sql = "
  SELECT $fieldList,
         s.name AS skill_name,
         r.full_name AS requester_name,
         p.full_name AS provider_name
    FROM transactions t
    JOIN skills    s ON s.skill_id   = t.skill_id
    JOIN students  r ON r.student_id = t.requester_id
    JOIN students  p ON p.student_id = t.provider_id
   WHERE t.transaction_id = ?";

$st = $pdo->prepare($sql);
$st->execute([$tid]);
$tx = $st->fetch();
if (!$tx) { http_response_code(404); exit('Thread not found'); }

if ($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']) {
  http_response_code(403); exit('Not allowed');
}
$isRequester = ($uid === (int)$tx['requester_id']);
$isProvider  = ($uid === (int)$tx['provider_id']);

$lastSeen = '1970-01-01 00:00:00';
if ($hasReadsT) {
  $q = $pdo->prepare('SELECT last_seen_at FROM message_reads WHERE user_id=? AND transaction_id=?');
  $q->execute([$uid, $tid]);
  $v = $q->fetchColumn();
  if ($v) $lastSeen = (string)$v;

  $up = $pdo->prepare("
    INSERT INTO message_reads (user_id, transaction_id, last_seen_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE last_seen_at = NOW()
  ");
  $up->execute([$uid, $tid]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();

  if (isset($_POST['send'], $_POST['body'])) {
    $body = trim((string)$_POST['body']);
    if ($body !== '') {
      $ins = $pdo->prepare(
        "INSERT INTO messages (transaction_id, sender_id, body, type)
         VALUES (:tid, :sid, :body, 'text')"
      );
      $ins->execute([':tid'=>$tid, ':sid'=>$uid, ':body'=>$body]);
    }
    redirect("/thread.php?id=$tid");
  }

  if ($hasProp && isset($_POST['propose'], $_POST['hours'])) {
    $ph = max(0.5, min(10.0, (float)$_POST['hours']));
    $pdo->beginTransaction();
    $pdo->prepare(
      "UPDATE transactions
          SET proposed_hours = ?, status = 'proposed'
        WHERE transaction_id = ?"
    )->execute([$ph, $tid]);
    $pdo->prepare(
      "INSERT INTO messages (transaction_id, sender_id, body, type)
       VALUES (?, ?, ?, 'proposal')"
    )->execute([$tid, $uid, "Proposed hours: {$ph}"]);
    $pdo->commit();
    redirect("/thread.php?id=$tid");
  }
}

$msgs = $pdo->prepare(
  "SELECT m.id, m.sender_id, m.body, m.type, m.created_at,
          s.full_name
     FROM messages m
     JOIN students s ON s.student_id = m.sender_id
    WHERE m.transaction_id = ?
 ORDER BY m.created_at ASC, m.id ASC"
);
$msgs->execute([$tid]);
$messages = $msgs->fetchAll();

$proposed = $hasProp ? ($tx['proposed_hours'] ?? null) : null;

$canReview = false;
$alreadyReviewed = false;
if ($hasReviewsT && $uid) {
  $chk = $pdo->prepare('SELECT 1 FROM reviews WHERE transaction_id=? AND reviewer_id=?');
  $chk->execute([(int)$tx['transaction_id'], $uid]);
  $alreadyReviewed = (bool)$chk->fetchColumn();

  $ask = ($_GET['review'] ?? '') === '1';
  if ($tx['status'] === 'confirmed' && !$alreadyReviewed) $canReview = true;
  if ($ask && !$alreadyReviewed) $canReview = true; 
}

$pageTitle = $tx['skill_name'];
include $ROOT . '/templates/header.php';
?>

<?php if (($_GET['e'] ?? '') === 'credits'): ?>
  <div class="notice error">Not enough credits to complete this request.</div>
<?php endif; ?>

<h1><?= h($tx['skill_name']) ?></h1>
<p class="muted">
  Status: <?= h($tx['status']) ?>
  • Hours: <?= number_format((float)$tx['hours'],2) ?>
  • Credits: <?= number_format((float)$tx['fuss_credit_amount'],2) ?>
  <?php if ($hasProp && $proposed !== null): ?>
    • Proposed: <?= number_format((float)$proposed,2) ?>
  <?php endif; ?>
</p>

<h2>Conversation</h2>
<ul class="list">
  <?php
    $lastSeenTs = strtotime($lastSeen);
    foreach ($messages as $m):
      $isNew = $hasReadsT && $m['created_at'] && (strtotime((string)$m['created_at']) > $lastSeenTs);
  ?>
    <li>
      <strong><?= h($m['full_name']) ?>:</strong>
      <?= nl2br(h($m['body'])) ?>
      <?php if ($m['type'] === 'system'): ?><span class="muted">(system)</span><?php endif; ?>
      <?php if ($m['type'] === 'proposal'): ?><span class="muted">(proposal)</span><?php endif; ?>
      <?php if ($m['type'] === 'text'): ?><span class="muted">(text)</span><?php endif; ?>
      <?php if ($isNew): ?>
        <span class="chip" style="background:var(--accent);color:#0b1220;margin-left:6px">new</span>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>

<?php if ($tx['status'] === 'confirmed'): ?>
  <div class="notice">This request is completed and locked.</div>
<?php else: ?>
  <form method="post" class="grid" style="margin-top:12px">
    <?= csrf_field() ?>
    <input type="text" name="body" placeholder="Write a message…" required>
    <button class="btn" name="send" value="1">Send</button>
  </form>

  <?php if ($hasProp): ?>
    <h2 style="margin-top:24px">Propose alternative</h2>
    <form method="post" class="grid grid--2">
      <?= csrf_field() ?>
      <input type="number" step="0.5" min="0.5" max="10" name="hours" value="<?= h((string)($proposed ?? $tx['hours'])) ?>">
      <button class="btn" name="propose" value="1">Propose</button>
    </form>
  <?php endif; ?>

  <h2 style="margin-top:24px">Confirm completion</h2>
  <form method="post" action="/actions/service_confirm.php" class="grid grid--2">
    <?= csrf_field() ?>
    <input type="hidden" name="tx_id" value="<?= (int)$tx['transaction_id'] ?>">
    <input type="number" step="0.5" min="0.5" name="hours" value="<?= h((string)$tx['hours']) ?>">
    <button class="btn btn--primary">Confirm</button>
  </form>
<?php endif; ?>

<?php
if ($hasReviewsT):
  if ($canReview): ?>
    <section class="card" id="review" style="margin-top:24px">
      <h2 style="margin-top:0">Leave a review</h2>
      <p class="muted" style="margin-top:-6px">Rate your experience and add an optional comment.</p>

      <form method="post" action="/actions/review_submit.php" class="grid grid--2">
        <?= csrf_field() ?>
        <input type="hidden" name="tx_id" value="<?= (int)$tx['transaction_id'] ?>">

        <div>
          <label class="label">Rating</label>
          <div class="chips" style="margin-top:8px">
            <?php for ($i=5; $i>=1; $i--): ?>
              <label class="chip" style="cursor:pointer">
                <input type="radio" name="stars" value="<?= $i ?>" <?= $i===5?'checked':'' ?>>
                <span style="margin-left:6px"><?= $i ?> ★</span>
              </label>
            <?php endfor; ?>
          </div>
        </div>

        <div>
          <label class="label">Comment (optional)</label>
          <textarea name="comment" rows="3" placeholder="What went well? Anything to improve?"></textarea>
        </div>

        <div>
          <button class="btn btn--primary">Submit review</button>
        </div>
      </form>
    </section>

    <script>
      (function(){
        const has = new URLSearchParams(location.search).get('review');
        if (has === '1') document.getElementById('review')?.scrollIntoView({behavior:'smooth'});
      }());
    </script>
  <?php elseif ($tx['status'] === 'confirmed' && $alreadyReviewed): ?>
    <section class="card" id="review" style="margin-top:24px">
      <p class="muted">Thanks — your review has been recorded.</p>
    </section>
  <?php endif;
endif; ?>

<?php include $ROOT . '/templates/footer.php';
