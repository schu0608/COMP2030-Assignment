<?php
$ROOT = dirname(__DIR__, 2);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$pdo = db();

$cid = (int)($_GET['id'] ?? 0);
if ($cid <= 0) { http_response_code(404); exit('Conversation not found'); }

$st = $pdo->prepare("
  SELECT c.conversation_id, c.a_id, c.b_id,
         CASE WHEN c.a_id=? THEN c.b_id ELSE c.a_id END AS other_id,
         s.full_name AS other_name, s.email AS other_email
    FROM conversations c
    JOIN students s ON s.student_id = (CASE WHEN c.a_id=? THEN c.b_id ELSE c.a_id END)
   WHERE c.conversation_id = ?
");
$st->execute([$uid, $uid, $cid]);
$conv = $st->fetch();
if (!$conv) { http_response_code(404); exit('Conversation not found'); }

if ($uid !== (int)$conv['a_id'] && $uid !== (int)$conv['b_id']) {
  http_response_code(403); exit('Not allowed');
}

$pageTitle = $conv['other_name'];

$pdo->prepare("UPDATE pm_messages SET read_at=NOW() WHERE conversation_id=? AND sender_id<>? AND read_at IS NULL")
    ->execute([$cid, $uid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
  validate_csrf();
  $body = trim((string)($_POST['body'] ?? ''));
  if ($body !== '') {
    $pdo->prepare("INSERT INTO pm_messages (conversation_id, sender_id, body) VALUES (?,?,?)")
        ->execute([$cid, $uid, $body]);
    $pdo->prepare("UPDATE conversations SET updated_at=updated_at WHERE conversation_id=?")->execute([$cid]);
  }
  redirect("/pm/thread.php?id={$cid}");
}

$ms = $pdo->prepare("
  SELECT m.id, m.sender_id, m.body, m.created_at, m.read_at,
         u.full_name
    FROM pm_messages m
    JOIN students u ON u.student_id = m.sender_id
   WHERE m.conversation_id = ?
ORDER BY m.created_at ASC, m.id ASC
");
$ms->execute([$cid]);
$messages = $ms->fetchAll();

include $ROOT . '/templates/header.php';
?>
<h1><?= h($conv['other_name']) ?></h1>
<p class="muted">Email: <?= h($conv['other_email']) ?></p>

<section class="card">
  <ul class="list" style="margin:0">
    <?php foreach ($messages as $m): ?>
      <li style="padding:6px 0">
        <strong><?= h($m['full_name']) ?>:</strong>
        <?= nl2br(h($m['body'])) ?>
        <span class="muted" style="margin-left:8px; font-size:.9em">
          <?= h(date('Y-m-d H:i', strtotime($m['created_at']))) ?>
          <?php if ((int)$m['sender_id'] === $uid && $m['read_at']): ?>
            • <em>read</em>
          <?php endif; ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>

  <form method="post" class="grid" style="margin-top:12px">
    <?= csrf_field() ?>
    <textarea name="body" rows="3" placeholder="Write a message…" required></textarea>
    <button class="btn btn--primary" name="send" value="1">Send</button>
  </form>
</section>

<?php include $ROOT . '/templates/footer.php';
