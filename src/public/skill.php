<?php
require_once dirname(__DIR__).'/inc/init.inc.php';

$offer_id = (int)($_GET['id'] ?? 0);

$sql = 'SELECT
          ss.id AS offer_id,
          s.skill_id,
          s.name,
          s.category,
          s.description AS skill_desc,
          st.student_id AS provider_id,
          st.full_name,
          st.academic_year,
          ss.details
        FROM student_skills ss
        JOIN skills s   ON s.skill_id = ss.skill_id
        JOIN students st ON st.student_id = ss.student_id
        WHERE ss.id = ? AND ss.role = "offered"';

$stmt = db()->prepare($sql);
$stmt->execute([$offer_id]);
$row = $stmt->fetch();

if (!$row) { http_response_code(404); echo 'Offer not found'; exit; }

$uid   = current_user_id();
$self  = $uid && $uid === (int)$row['provider_id'];
$open  = null;

// Check if the current user already has an OPEN transaction for this skill with this provider
if ($uid && !$self) {
  $chk = db()->prepare(
    'SELECT transaction_id, status
       FROM transactions
      WHERE requester_id = ?
        AND provider_id  = ?
        AND skill_id     = ?
        AND status IN ("pending","accepted","proposed","confirm_requester","confirm_provider")
      ORDER BY transaction_id DESC
      LIMIT 1'
  );
  $chk->execute([$uid, (int)$row['provider_id'], (int)$row['skill_id']]);
  $open = $chk->fetch();
}

// Optional banner if we came back after creating a request
$ok      = $_GET['ok'] ?? '';
$threadId= isset($_GET['tid']) ? (int)$_GET['tid'] : null;
?>

<?php include dirname(__DIR__).'/templates/header.php'; ?>

<article class="card">
  <h1><?= h($row['name']) ?></h1>
  <p class="muted">
    Category: <?= h($row['category']) ?>
    • by <a href="/profile.php?u=<?= (int)$row['provider_id'] ?>"><?= h($row['full_name']) ?></a>
  </p>

  <?php if (!empty($row['skill_desc'])): ?>
    <p><?= nl2br(h($row['skill_desc'])) ?></p>
  <?php endif; ?>

  <?php if (!empty($row['details'])): ?>
    <p><strong>Provider notes:</strong> <?= nl2br(h($row['details'])) ?></p>
  <?php endif; ?>

  <?php if ($ok === 'sent'): ?>
    <div class="notice">
      Request sent — awaiting <?= h($row['full_name']) ?>’s confirmation.
      <?php if ($threadId): ?> <a href="/thread.php?id=<?= $threadId ?>">View conversation</a><?php endif; ?>
    </div>
  <?php endif; ?>

  <h2>Request this service</h2>

  <?php if (!$uid): ?>
    <p><a href="/auth/login.php">Log in</a> to request this service.</p>

  <?php elseif ($self): ?>
    <p class="muted">You can’t request your own offer.</p>

  <?php elseif ($open): ?>
    <div class="notice">
      Request already sent — status: <strong><?= h($open['status']) ?></strong>.
      <a href="/thread.php?id=<?= (int)$open['transaction_id'] ?>">View conversation</a>
    </div>

  <?php else: ?>
    <form method="post" action="/actions/request_create.php">
      <?= csrf_field() ?>
      <!-- Only post offer_id; server resolves provider and skill -->
      <input type="hidden" name="offer_id" value="<?= (int)$row['offer_id'] ?>">
      <label>Estimated hours
        <input type="number" name="hours" min="0.5" step="0.5" max="10" required>
      </label>
      <button class="btn">Send request</button>
    </form>
  <?php endif; ?>

</article>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>