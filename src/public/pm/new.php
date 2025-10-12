<?php
// /src/public/pm/new.php
$ROOT = dirname(__DIR__, 2);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$pdo = db();
$pageTitle = 'New message';

function normalize_pair(int $a, int $b): array { return ($a < $b) ? [$a,$b] : [$b,$a]; }

$to = (int)($_GET['to'] ?? 0);
$msg = "";

/* If posting, create/reuse conversation and redirect to thread */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  validate_csrf();
  $to = (int)($_POST['to'] ?? 0);
  $body = trim((string)($_POST['body'] ?? ''));

  if ($to <= 0) {
    $msg = "Please choose a recipient.";
  } else {
    // Verify recipient exists & active
    $st = $pdo->prepare("SELECT student_id, full_name FROM students WHERE student_id=? AND active=1");
    $st->execute([$to]);
    $peer = $st->fetch();
    if (!$peer) {
      $msg = "Recipient not found.";
    } else {
      [$a,$b] = normalize_pair($uid, $to);

      // Find or create conversation
      $find = $pdo->prepare("SELECT conversation_id FROM conversations WHERE a_id=? AND b_id=?");
      $find->execute([$a,$b]);
      $cid = (int)$find->fetchColumn();

      if (!$cid) {
        $ins = $pdo->prepare("INSERT INTO conversations (a_id,b_id) VALUES (?,?)");
        $ins->execute([$a,$b]);
        $cid = (int)$pdo->lastInsertId();
      }

      if ($body !== '') {
        $pdo->prepare("INSERT INTO pm_messages (conversation_id, sender_id, body) VALUES (?,?,?)")
            ->execute([$cid, $uid, $body]);
      }

      redirect("/pm/thread.php?id={$cid}");
    }
  }
}

/* Quick search for a person if no ?to */
$s = trim((string)($_GET['s'] ?? ''));
$people = [];
if ($s !== '') {
  $ps = $pdo->prepare("
    SELECT student_id, full_name, email
      FROM students
     WHERE active=1 AND student_id<>?
       AND (full_name LIKE ? OR email LIKE ?)
     ORDER BY full_name
     LIMIT 25
  ");
  $like = "%{$s}%";
  $ps->execute([$uid, $like, $like]);
  $people = $ps->fetchAll();
}

include $ROOT . '/templates/header.php';
?>
<h1>New message</h1>

<?php if ($msg): ?><div class="notice error"><?= h($msg) ?></div><?php endif; ?>

<?php if (!$to): ?>
  <form method="get" class="search-inline" action="/pm/new.php" style="display:flex;gap:8px;margin-bottom:12px">
    <input type="text" name="s" value="<?= h($s) ?>" placeholder="Find people (name or email)…">
    <button class="btn">Search</button>
    <?php if ($s!==''): ?><a class="btn btn--ghost" href="/pm/new.php">Clear</a><?php endif; ?>
  </form>

  <?php if ($s !== ''): ?>
    <?php if (!$people): ?>
      <div class="card" style="padding:12px"><p class="muted" style="margin:0">No matches for “<?= h($s) ?>”.</p></div>
    <?php else: ?>
      <div class="inbox-grid">
        <?php foreach ($people as $p): ?>
          <article class="inbox-item">
            <header class="inbox-head">
              <h3 class="title"><?= h($p['full_name']) ?></h3>
              <span class="muted"><?= h($p['email']) ?></span>
            </header>
            <p class="muted">Start a conversation.</p>
            <footer class="inbox-foot">
              <a class="btn btn--sm" href="/pm/new.php?to=<?= (int)$p['student_id'] ?>">Choose</a>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>
  <?php
    $who = $pdo->prepare("SELECT full_name, email FROM students WHERE student_id=? AND active=1");
    $who->execute([$to]);
    $peer = $who->fetch();
  ?>
  <?php if (!$peer): ?>
    <div class="notice error">Recipient not found or inactive.</div>
  <?php else: ?>
    <section class="card">
      <h2 style="margin-top:0">Message <?= h($peer['full_name']) ?></h2>
      <form method="post" class="grid">
        <?= csrf_field() ?>
        <input type="hidden" name="to" value="<?= (int)$to ?>">
        <textarea name="body" rows="3" placeholder="Write a message…" required></textarea>
        <button class="btn btn--primary">Send</button>
      </form>
    </section>
  <?php endif; ?>
<?php endif; ?>

<?php include $ROOT . '/templates/footer.php';
