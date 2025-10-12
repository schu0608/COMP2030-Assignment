<?php
// src/public/thread.php
$ROOT = dirname(__DIR__);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$tid = (int)($_GET['id'] ?? 0);
if ($tid <= 0) { http_response_code(404); exit('Thread not found'); }

$pdo = db();

/** helper: does a table have a column? */
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

$hasProp = db_has_column($pdo, 'transactions', 'proposed_hours');

/** -------- Load transaction header -------- */
$fieldList = "t.transaction_id,t.requester_id,t.provider_id,t.skill_id,
              t.hours,t.fuss_credit_amount,t.status";
if ($hasProp) { $fieldList .= ",t.proposed_hours"; }

$sql = "
  SELECT $fieldList,
         s.name AS skill_name,
         r.full_name AS requester_name,
         p.full_name AS provider_name
    FROM transactions t
    JOIN skills    s ON s.skill_id = t.skill_id
    JOIN students  r ON r.student_id = t.requester_id
    JOIN students  p ON p.student_id = t.provider_id
   WHERE t.transaction_id = ?";

$st = $pdo->prepare($sql);
$st->execute([$tid]);
$tx = $st->fetch();
if (!$tx) { http_response_code(404); exit('Thread not found'); }

// permission
if ($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']) {
  http_response_code(403); exit('Not allowed');
}

$isRequester = ($uid === (int)$tx['requester_id']);
$isProvider  = ($uid === (int)$tx['provider_id']);

/** -------- Actions (POST) -------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();

  // Send message
  if (isset($_POST['send']) && isset($_POST['body'])) {
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

  // Propose alternative hours (only if column exists)
  if ($hasProp && isset($_POST['propose']) && isset($_POST['hours'])) {
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

// convenience
$proposed = $hasProp ? ($tx['proposed_hours'] ?? null) : null;

$pageTitle = $tx['skill_name'];
include $ROOT . '/templates/header.php';
?>

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
  <?php foreach ($messages as $m): ?>
    <li>
      <strong><?= h($m['full_name']) ?>:</strong>
      <?= nl2br(h($m['body'])) ?>
      <?php if ($m['type'] === 'system'): ?><span class="muted">(system)</span><?php endif; ?>
      <?php if ($m['type'] === 'proposal'): ?><span class="muted">(proposal)</span><?php endif; ?>
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
  <form method="post" class="grid grid--2">
    <?= csrf_field() ?>
    <input type="number" step="0.5" min="0.5" name="final_hours" value="<?= h((string)$tx['hours']) ?>">
    <button class="btn btn--primary" name="confirm" value="1">Confirm</button>
  </form>
<?php endif; ?>

<?php include $ROOT . '/templates/footer.php';