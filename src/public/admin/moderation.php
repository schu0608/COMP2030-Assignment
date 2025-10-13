<?php
// public/admin/dashboard.php (and other admin pages)
require_once dirname(__DIR__, 2) . '/inc/init.inc.php';   // or auth.inc.php – wherever require_login() lives
$uid = require_login(); // now safe to call

$uid = require_login();
if (!function_exists('is_admin') ? $uid !== 1 : !is_admin($uid)) {
  http_response_code(403);
  exit('Forbidden');
}

$pdo = db();
$msg = "";

/* --- detect whether the column is `content_type` or `type` --- */
$typeCol = 'content_type';
try {
  $stmt = $pdo->query("
    SELECT COLUMN_NAME
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'flagged_content'
       AND COLUMN_NAME IN ('content_type','type')
     LIMIT 1
  ");
  if ($row = $stmt->fetch()) {
    $typeCol = $row['COLUMN_NAME'];
  }
} catch (Throwable $e) {
  // keep default
}

/* --- actions --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  // Single remove: one submit button with name=remove_one and value=<id>
  if (isset($_POST['remove_one'])) {
    $id = (int)$_POST['remove_one'];
    if ($id > 0) {
      $del = $pdo->prepare("DELETE FROM flagged_content WHERE id=?");
      $ok  = $del->execute([$id]);
      $msg = $ok ? "Removed flagged item #{$id}." : "Remove failed.";
    } else {
      $msg = "Invalid item id.";
    }
  }

  // Bulk remove: checkboxes named ids[]
  if (isset($_POST['bulk_remove']) && !empty($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_values(array_filter(array_map('intval', $_POST['ids']), static fn($n)=>$n>0));
    if ($ids) {
      $place = implode(',', array_fill(0, count($ids), '?'));
      $del   = $pdo->prepare("DELETE FROM flagged_content WHERE id IN ($place)");
      $ok    = $del->execute($ids);
      $msg   = $ok ? "Removed ".count($ids)." item(s)." : "Bulk remove failed.";
    } else {
      $msg = "No items selected.";
    }
  }
}

/* --- fetch flagged items --- */
$rows = [];
try {
  $sql = "SELECT id, {$typeCol} AS ctype, content, reported_by, created_at
            FROM flagged_content
        ORDER BY created_at DESC";
  $rows = $pdo->query($sql)->fetchAll();
} catch (Throwable $e) {
  $rows = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Content Moderation</title>
  <style>
    :root{
      --bg:#0f172a; --card:#111827; --mut:#94a3b8; --text:#e5e7eb;
      --accent:#FFCC00; --ring:#1f2937; --chip:#1f2937; --border:#1f2937;
      --danger:#ef4444;
    }
    *{box-sizing:border-box}
    body{margin:0; padding:32px; background:linear-gradient(180deg,#0b1220,#0f172a); color:var(--text); font-family:system-ui,Segoe UI,Arial,sans-serif}
    .container{max-width:1100px; margin:0 auto}
    header{margin-bottom:12px}
    h1{margin:0 0 6px}
    .sub{color:var(--mut); margin:6px 0}
    a{color:#cbd5e1; text-decoration:none}
    a:hover{text-decoration:underline}

    .card{background:var(--card); border:1px solid var(--ring); border-radius:16px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,.25); margin-bottom:16px}
    .bar{display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:space-between}

    .btn{padding:10px 14px; border-radius:10px; border:1px solid var(--ring); background:#111827; color:#e5e7eb; cursor:pointer}
    .btn:hover{background:#0f172a}
    .btn.danger{background:var(--danger); border-color:#b91c1c; color:#0b1220}
    .btn.danger:hover{background:#dc2626}

    .msg{background:#0b2130; border:1px solid #1f3a4d; color:#dbeafe; padding:10px 12px; border-radius:10px; margin-bottom:12px}

    .table-wrap{overflow:auto}
    table{border-collapse:collapse; width:100%}
    th,td{padding:10px 12px; border-bottom:1px solid var(--ring); text-align:left; vertical-align:top}
    thead th{color:#cbd5e1; font-weight:700}
    .empty{color:#94a3b8; text-align:center}
    .actions{display:flex; gap:8px}
    input[type=checkbox]{transform:scale(1.1)}
  </style>
</head>
<body class="admin">
<div class="container">

  <header>
    <h1>Content Moderation</h1>
    <p class="sub">Monitor and remove inappropriate content in profiles, skill descriptions, or messages.</p>
    <p><a href="/admin/dashboard.php">← Back to Dashboard</a></p>
  </header>

  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

  <section class="card">
    <?php if (empty($rows)): ?>
      <p class="empty">No flagged content right now. 🎉</p>
    <?php else: ?>
    <form method="post">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <div class="bar">
        <div class="sub">Showing <?= number_format(count($rows)) ?> flagged item(s)</div>
        <div>
          <button name="bulk_remove" value="1" class="btn danger" onclick="return confirm('Remove selected items?')">Remove Selected</button>
        </div>
      </div>

      <div class="table-wrap">
        <table class="zebra compact">
          <thead>
            <tr>
              <th style="width:44px"><input type="checkbox" onclick="document.querySelectorAll('.js-rowchk').forEach(c=>c.checked=this.checked)"></th>
              <th style="width:70px">ID</th>
              <th style="width:160px">Type</th>
              <th>Content</th>
              <th style="width:140px">Reported By</th>
              <th style="width:180px">When</th>
              <th style="width:120px">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><input class="js-rowchk" type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
                <td><?= (int)$r['id'] ?></td>
                <td><?= h($r['ctype'] ?? '') ?></td>
                <td style="white-space:pre-wrap"><?= h($r['content'] ?? '') ?></td>
                <td><?= (int)($r['reported_by'] ?? 0) ?></td>
                <td><?= h((string)($r['created_at'] ?? '')) ?></td>
                <td class="actions">
                  <button name="remove_one" value="<?= (int)$r['id'] ?>" class="btn danger" onclick="return confirm('Remove this content?');">Remove</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </form>
    <?php endif; ?>
  </section>

</div>
</body>
</html>
