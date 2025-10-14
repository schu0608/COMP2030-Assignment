<?php
require_once dirname(__DIR__).'/inc/init.inc.php';

$offer_id = (int)($_GET['id'] ?? 0);
if ($offer_id <= 0) { http_response_code(400); echo 'Bad request'; exit; }

$sql = 'SELECT
          ss.id            AS offer_id,
          s.skill_id       AS skill_id,
          s.name           AS skill_name,
          s.category       AS category,
          s.description    AS skill_desc,
          st.student_id    AS provider_id,
          st.full_name     AS provider_name,
          st.academic_year AS provider_year,
          ss.details       AS provider_notes
        FROM student_skills ss
        JOIN skills   s  ON s.skill_id = ss.skill_id
        JOIN students st ON st.student_id = ss.student_id
        WHERE ss.id = ? AND ss.role = "offered"';
$st = db()->prepare($sql);
$st->execute([$offer_id]);
$row = $st->fetch();

if (!$row) { http_response_code(404); echo 'Offer not found'; exit; }

$uid      = current_user_id();
$self     = $uid && $uid === (int)$row['provider_id'];
$pageTitle= $row['skill_name'] ?? 'Skill';

$open = null;
if ($uid && !$self) {
  $openSt = db()->prepare(
    'SELECT transaction_id, status
       FROM transactions
      WHERE requester_id = ?
        AND provider_id  = ?
        AND skill_id     = ?
        AND status IN ("pending","accepted","proposed","confirm_requester","confirm_provider")
      ORDER BY transaction_id DESC
      LIMIT 1'
  );
  $openSt->execute([$uid, (int)$row['provider_id'], (int)$row['skill_id']]);
  $open = $openSt->fetch();
}

$ok       = (string)($_GET['ok'] ?? '');
$threadId = isset($_GET['tid']) ? (int)$_GET['tid'] : null;

$myCredits = null;
if ($uid) {
  $myCredits = db()->query('SELECT fuss_credits FROM students WHERE student_id='.(int)$uid)->fetchColumn();
}
?>
<?php include dirname(__DIR__).'/templates/header.php'; ?>

<article class="card">
  <h1><?= h($row['skill_name']) ?></h1>
  <p class="muted">
    Category: <?= h($row['category']) ?> •
    by <a href="/profile.php?u=<?= (int)$row['provider_id'] ?>"><?= h($row['provider_name']) ?></a>
    <?php if ((int)$row['provider_year']): ?> (Year <?= (int)$row['provider_year'] ?>)<?php endif; ?>
  </p>

  <?php if (!empty($row['skill_desc'])): ?>
    <p><?= nl2br(h($row['skill_desc'])) ?></p>
  <?php endif; ?>

  <?php if (!empty($row['provider_notes'])): ?>
    <p><strong>Provider notes:</strong> <?= nl2br(h($row['provider_notes'])) ?></p>
  <?php endif; ?>

  <?php if ($ok === 'sent'): ?>
    <div class="notice">
      Request sent — awaiting <?= h($row['provider_name']) ?>’s response.
      <?php if ($threadId): ?>
        <a href="/thread.php?id=<?= $threadId ?>">Open conversation</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <h2>Request this service</h2>

  <?php if (!$uid): ?>
    <p><a href="/auth/login.php">Log in</a> to request this service.</p>

  <?php elseif ($self): ?>
    <p class="muted">You can’t request your own offer.</p>

  <?php elseif ($open): ?>
    <div class="notice">
      You already have a request in progress (status:
      <strong><?= h($open['status']) ?></strong>).
      <a href="/thread.php?id=<?= (int)$open['transaction_id'] ?>">View conversation</a>
    </div>

  <?php else: ?>
    <form method="post" action="/actions/request_create.php" class="grid grid--2">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <input type="hidden" name="offer_id" value="<?= (int)$row['offer_id'] ?>">

      <label class="label">Estimated hours
        <input type="number" name="hours" min="0.5" step="0.5" max="10" required>
      </label>

      <div>
        <button class="btn btn--primary" style="margin-top:24px">Send request</button>
        <?php if ($myCredits !== null): ?>
          <div class="muted" style="margin-top:6px">
            You currently have <strong><?= number_format((float)$myCredits, 2) ?></strong> credits.
            (1 hour = 1 credit)
          </div>
        <?php endif; ?>
      </div>

      <label class="label" style="grid-column:1 / -1">Optional message to provider
        <textarea name="message" rows="3" placeholder="Briefly describe what you need…"></textarea>
      </label>
    </form>
  <?php endif; ?>
</article>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
