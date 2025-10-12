<?php
// /src/public/pm/index.php
$ROOT = dirname(__DIR__, 2);
require_once $ROOT . '/inc/init.inc.php';

$uid = require_login();
$pdo = db();
$pageTitle = 'Messages';

$q = trim((string)($_GET['q'] ?? ''));          // search by name/email
$params = [':me' => $uid];

// Build optional search filter
$filter = '';
if ($q !== '') {
  $filter = " AND (u.full_name LIKE :q OR u.email LIKE :q) ";
  $params[':q'] = "%{$q}%";
}

/*
  Only show direct conversations that already have activity:
  - user is either a_id or b_id
  - EXISTS(SELECT 1 FROM pm_messages ...) guarantees at least one message
  - last message body/time obtained with scalar subqueries
  - unread_count = messages by the OTHER user with read_at IS NULL
*/
$sql = "
  SELECT
    c.conversation_id,
    CASE WHEN c.a_id = :me THEN c.b_id ELSE c.a_id END AS other_id,
    u.full_name AS other_name,
    u.email     AS other_email,

    /* last message text + time */
    ( SELECT m.body
        FROM pm_messages m
       WHERE m.conversation_id = c.conversation_id
       ORDER BY m.created_at DESC, m.id DESC
       LIMIT 1
    ) AS last_body,

    ( SELECT m.created_at
        FROM pm_messages m
       WHERE m.conversation_id = c.conversation_id
       ORDER BY m.created_at DESC, m.id DESC
       LIMIT 1
    ) AS last_at,

    /* unread from the other user */
    ( SELECT COUNT(*)
        FROM pm_messages m
       WHERE m.conversation_id = c.conversation_id
         AND m.sender_id <> :me
         AND m.read_at IS NULL
    ) AS unread_count

  FROM conversations c
  JOIN students u
    ON u.student_id = (CASE WHEN c.a_id = :me THEN c.b_id ELSE c.a_id END)

  WHERE (c.a_id = :me OR c.b_id = :me)
    AND EXISTS (SELECT 1 FROM pm_messages x WHERE x.conversation_id = c.conversation_id)
    $filter

  ORDER BY last_at DESC, c.conversation_id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

include $ROOT . '/templates/header.php';
?>
<h1>Messages</h1>

<div class="bar" style="gap:12px; margin-bottom:12px; flex-wrap:wrap">
  <a class="btn btn--primary" href="/pm/new.php">New message</a>

  <form method="get" class="search-inline" action="" style="display:flex; gap:8px">
    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search people by name or email…">
    <button class="btn btn--sm">Search</button>
    <?php if ($q !== ''): ?>
      <a class="btn btn--sm btn--ghost" href="/pm/index.php">Clear</a>
    <?php endif; ?>
  </form>
</div>

<?php if (!$rows): ?>
  <div class="card" style="padding:14px">
    <p class="muted" style="margin:0">
      No direct conversations yet<?= $q ? ' for this search' : '' ?>.
      Start one with <a href="/pm/new.php">New message</a>.
    </p>
  </div>
<?php else: ?>
  <div class="inbox-grid">
    <?php foreach ($rows as $r): ?>
      <article class="inbox-item">
        <header class="inbox-head">
          <h3 class="title"><?= h($r['other_name']) ?></h3>
          <?php if ((int)$r['unread_count'] > 0): ?>
            <span class="pill warning"><?= (int)$r['unread_count'] ?> unread</span>
          <?php endif; ?>
        </header>

        <?php if (!empty($r['last_body'])): ?>
          <p class="last">
            <?= h($r['last_body']) ?>
            <?php if (!empty($r['last_at'])): ?>
              <span class="muted" style="margin-left:8px; font-size:.9em">
                • <?= h(date('Y-m-d H:i', strtotime($r['last_at']))) ?>
              </span>
            <?php endif; ?>
          </p>
        <?php else: ?>
          <p class="muted">No messages yet.</p>
        <?php endif; ?>

        <footer class="inbox-foot">
          <a class="btn btn--sm" href="/pm/thread.php?id=<?= (int)$r['conversation_id'] ?>">Open</a>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include $ROOT . '/templates/footer.php';
