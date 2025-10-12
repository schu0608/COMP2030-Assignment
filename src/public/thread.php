<?php
// src/public/thread.php
$ROOT = dirname(__DIR__);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$tid = (int)($_GET['id'] ?? 0);
if ($tid <= 0) { http_response_code(404); exit('Thread not found'); }

$pdo = db();

<<<<<<< HEAD
/** ---------- helpers ---------- */
=======
/** helper: does a table have a column? */
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
function db_has_column(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare(
    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?"
  );
  $q->execute([$table, $col]);
  return (bool)$q->fetchColumn();
<<<<<<< HEAD
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

/** ---------- load transaction header ---------- */
=======
}

$hasProp = db_has_column($pdo, 'transactions', 'proposed_hours');

/** -------- Load transaction header -------- */
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
$fieldList = "t.transaction_id,t.requester_id,t.provider_id,t.skill_id,
              t.hours,t.fuss_credit_amount,t.status";
if ($hasProp) { $fieldList .= ",t.proposed_hours"; }

$sql = "
  SELECT $fieldList,
         s.name AS skill_name,
         r.full_name AS requester_name,
         p.full_name AS provider_name
    FROM transactions t
<<<<<<< HEAD
    JOIN skills    s ON s.skill_id   = t.skill_id
=======
    JOIN skills    s ON s.skill_id = t.skill_id
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
    JOIN students  r ON r.student_id = t.requester_id
    JOIN students  p ON p.student_id = t.provider_id
   WHERE t.transaction_id = ?";

$st = $pdo->prepare($sql);
$st->execute([$tid]);
$tx = $st->fetch();
if (!$tx) { http_response_code(404); exit('Thread not found'); }

<<<<<<< HEAD
// permissions
if ($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']) {
  http_response_code(403); exit('Not allowed');
}
$isRequester = ($uid === (int)$tx['requester_id']);
$isProvider  = ($uid === (int)$tx['provider_id']);

/** ---------- unread tracking: get last seen, then mark now ---------- */
$lastSeen = '1970-01-01 00:00:00';
if ($hasReadsT) {
  // read previous last_seen_at
  $q = $pdo->prepare('SELECT last_seen_at FROM message_reads WHERE user_id=? AND transaction_id=?');
  $q->execute([$uid, $tid]);
  $v = $q->fetchColumn();
  if ($v) $lastSeen = (string)$v;

  // upsert current seen time to NOW() so nav badge clears
  $up = $pdo->prepare("
    INSERT INTO message_reads (user_id, transaction_id, last_seen_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE last_seen_at = NOW()
  ");
  $up->execute([$uid, $tid]);
}

/** ---------- actions: send / propose (confirm handled by actions/service_confirm.php) ---------- */
=======
// permission
if ($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']) {
  http_response_code(403); exit('Not allowed');
}

$isRequester = ($uid === (int)$tx['requester_id']);
$isProvider  = ($uid === (int)$tx['provider_id']);

/** -------- Actions (POST) -------- */
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();

  // Send message
<<<<<<< HEAD
  if (isset($_POST['send'], $_POST['body'])) {
=======
  if (isset($_POST['send']) && isset($_POST['body'])) {
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
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

<<<<<<< HEAD
  // Propose alternative hours (if supported)
  if ($hasProp && isset($_POST['propose'], $_POST['hours'])) {
=======
  // Propose alternative hours (only if column exists)
  if ($hasProp && isset($_POST['propose']) && isset($_POST['hours'])) {
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
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
<<<<<<< HEAD
}

/** ---------- load conversation ---------- */
=======

  // Confirm completion (locks the thread)
  if (isset($_POST['confirm']) && isset($_POST['final_hours'])) {
    $h = max(0.5, min(100.0, (float)$_POST['final_hours']));

    // choose next status -> both sides must confirm before 'confirmed'
    $next = $isRequester ? 'confirm_requester' : 'confirm_provider';
    if ($tx['status'] === ($isRequester ? 'confirm_provider' : 'confirm_requester')) {
      $next = 'confirmed';
    }

    $pdo->beginTransaction();

    // If you want credits == hours, keep this. Otherwise compute.
    $pdo->prepare(
      "UPDATE transactions
          SET hours = ?, fuss_credit_amount = ?, status = ?
        WHERE transaction_id = ?"
    )->execute([$h, $h, $next, $tid]);

    $pdo->prepare(
      "INSERT INTO messages (transaction_id, sender_id, body, type)
       VALUES (?,?,?, 'system')"
    )->execute([$tid, $uid, ($next==='confirmed' ? "Service confirmed ({$h} h)" : "Confirmation recorded ({$h} h)")]);

    // Transfer credits only when fully confirmed
    if ($next === 'confirmed') {
      $req = (int)$tx['requester_id'];
      $pro = (int)$tx['provider_id'];

      // Prevent negative requester balance
      $bal = (float)$pdo->query("SELECT fuss_credits FROM students WHERE student_id={$req} FOR UPDATE")->fetchColumn();
      if ($bal < $h) { $pdo->rollBack(); redirect("/thread.php?id=$tid&e=credits"); }

      $pdo->prepare("UPDATE students SET fuss_credits = fuss_credits - ? WHERE student_id=?")
          ->execute([$h, $req]);
      $pdo->prepare("UPDATE students SET fuss_credits = fuss_credits + ? WHERE student_id=?")
          ->execute([$h, $pro]);
    }

    $pdo->commit();
    redirect("/thread.php?id=$tid");
  }
}

/** -------- Load conversation -------- */
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
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

<<<<<<< HEAD
$proposed = $hasProp ? ($tx['proposed_hours'] ?? null) : null;

/** ---------- review prompt state ---------- */
$canReview = false;
$alreadyReviewed = false;
if ($hasReviewsT && $uid) {
  $chk = $pdo->prepare('SELECT 1 FROM reviews WHERE transaction_id=? AND reviewer_id=?');
  $chk->execute([(int)$tx['transaction_id'], $uid]);
  $alreadyReviewed = (bool)$chk->fetchColumn();

  $ask = ($_GET['review'] ?? '') === '1';
  if ($tx['status'] === 'confirmed' && !$alreadyReviewed) $canReview = true;
  if ($ask && !$alreadyReviewed) $canReview = true; // force display on redirect
}

/** ---------- page ---------- */
=======
// convenience
$proposed = $hasProp ? ($tx['proposed_hours'] ?? null) : null;

>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
$pageTitle = $tx['skill_name'];
include $ROOT . '/templates/header.php';
?>

<<<<<<< HEAD
<?php if (($_GET['e'] ?? '') === 'credits'): ?>
  <div class="notice error">Not enough credits to complete this request.</div>
<?php endif; ?>

=======
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
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
<<<<<<< HEAD
  <?php
    $lastSeenTs = strtotime($lastSeen);
    foreach ($messages as $m):
      $isNew = $hasReadsT && $m['created_at'] && (strtotime((string)$m['created_at']) > $lastSeenTs);
  ?>
=======
  <?php foreach ($messages as $m): ?>
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
    <li>
      <strong><?= h($m['full_name']) ?>:</strong>
      <?= nl2br(h($m['body'])) ?>
      <?php if ($m['type'] === 'system'): ?><span class="muted">(system)</span><?php endif; ?>
      <?php if ($m['type'] === 'proposal'): ?><span class="muted">(proposal)</span><?php endif; ?>
<<<<<<< HEAD
      <?php if ($m['type'] === 'text'): ?><span class="muted">(text)</span><?php endif; ?>
      <?php if ($isNew): ?>
        <span class="chip" style="background:var(--accent);color:#0b1220;margin-left:6px">new</span>
      <?php endif; ?>
=======
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
    </li>
  <?php endforeach; ?>
</ul>

<?php if ($tx['status'] === 'confirmed'): ?>
  <div class="notice">This request is completed and locked.</div>
<?php else: ?>
<<<<<<< HEAD
  <!-- send message -->
=======
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
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

<<<<<<< HEAD
  <!-- final confirmation (credits move happens in actions/service_confirm.php) -->
  <h2 style="margin-top:24px">Confirm completion</h2>
  <form method="post" action="/actions/service_confirm.php" class="grid grid--2">
    <?= csrf_field() ?>
    <input type="hidden" name="tx_id" value="<?= (int)$tx['transaction_id'] ?>">
    <input type="number" step="0.5" min="0.5" name="hours" value="<?= h((string)$tx['hours']) ?>">
    <button class="btn btn--primary">Confirm</button>
  </form>
<?php endif; ?>

<?php
/* -------------------------- Review Prompt -------------------------- */
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
=======
  <h2 style="margin-top:24px">Confirm completion</h2>
  <form method="post" class="grid grid--2">
    <?= csrf_field() ?>
    <input type="number" step="0.5" min="0.5" name="final_hours" value="<?= h((string)$tx['hours']) ?>">
    <button class="btn btn--primary" name="confirm" value="1">Confirm</button>
  </form>
<?php endif; ?>

<?php include $ROOT . '/templates/footer.php';
>>>>>>> d87cc6609613933511d699835a909f373fc4d4b0
