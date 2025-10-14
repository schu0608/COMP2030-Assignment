<?php
require_once dirname(__DIR__, 2) . '/inc/init.inc.php'; 
$uid = require_login();


$uid = require_login();
if (!function_exists('is_admin') ? $uid !== 1 : !is_admin($uid)) {
  http_response_code(403);
  exit('Forbidden');
}

$pdo = db();
$msg = "";

$detailsCol = 'description';
try {
  $stmt = $pdo->query("
    SELECT COLUMN_NAME
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'skills'
       AND COLUMN_NAME IN ('description','topics')
     LIMIT 1
  ");
  if ($row = $stmt->fetch()) {
    $detailsCol = $row['COLUMN_NAME'];
  }
} catch (Throwable $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  if (isset($_POST['add'])) {
    $category = trim((string)($_POST['category'] ?? ''));
    $name     = trim((string)($_POST['name'] ?? ''));
    $details  = trim((string)($_POST['details'] ?? ''));
    if ($category !== '' && $name !== '') {
      $sql = $detailsCol === 'topics'
        ? "INSERT INTO skills (name, category, topics) VALUES (?, ?, ?)"
        : "INSERT INTO skills (name, category, description) VALUES (?, ?, ?)";
      $st = $pdo->prepare($sql);
      $ok = $st->execute([$name, $category, $details]);
      $msg = $ok ? "Added skill: ".h($name) : "Add failed.";
    } else {
      $msg = "Category and Skill are required.";
    }
  }

  if (isset($_POST['update'])) {
    $id       = (int)($_POST['skill_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? ''));
    $name     = trim((string)($_POST['name'] ?? ''));
    $details  = trim((string)($_POST['details'] ?? ''));
    if ($id > 0 && $category !== '' && $name !== '') {
      $sql = $detailsCol === 'topics'
        ? "UPDATE skills SET name=?, category=?, topics=? WHERE skill_id=?"
        : "UPDATE skills SET name=?, category=?, description=? WHERE skill_id=?";
      $st = $pdo->prepare($sql);
      $ok = $st->execute([$name, $category, $details, $id]);
      $msg = $ok ? "Updated skill #{$id}" : "Update failed.";
    } else {
      $msg = "Missing required fields for update.";
    }
  }

  if (isset($_POST['delete'])) {
    $id = (int)($_POST['skill_id'] ?? 0);
    if ($id > 0) {
      $st = $pdo->prepare("DELETE FROM skills WHERE skill_id=?");
      $ok = $st->execute([$id]);
      $msg = $ok ? "Deleted skill #{$id}" : "Delete failed.";
    } else {
      $msg = "Invalid skill id.";
    }
  }
}

$rows = [];
try {
  $sql = "SELECT skill_id, category, name, {$detailsCol} AS details
            FROM skills
        ORDER BY category, name";
  $rows = $pdo->query($sql)->fetchAll();
} catch (Throwable $e) {
  $rows = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Skills & Categories</title>
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
    .bar{display:flex; gap:10px; align-items:center; flex-wrap:wrap}

    input, select{
      padding:10px; border-radius:10px; border:1px solid var(--border);
      background:#0f172a; color:#e5e7eb;
    }
    .in-cell{width:100%}
    .in-cell.wide{min-width:260px}

    .btn{padding:10px 14px; border-radius:10px; border:1px solid var(--ring); background:#111827; color:#e5e7eb; cursor:pointer}
    .btn:hover{background:#0f172a}
    .btn.sm{padding:6px 10px; font-size:.9rem}
    .btn.danger{background:var(--danger); border-color:#b91c1c; color:#0b1220}
    .btn.danger:hover{background:#dc2626}

    .msg{background:#0b2130; border:1px solid #1f3a4d; color:#dbeafe; padding:10px 12px; border-radius:10px; margin-bottom:12px}

    .table-wrap{overflow:auto}
    table{border-collapse:collapse; width:100%}
    th,td{padding:10px 12px; border-bottom:1px solid var(--ring); text-align:left; vertical-align:top}
    thead th{color:#cbd5e1; font-weight:700}
    .empty{color:#94a3b8; text-align:center}
    .actions{display:flex; gap:8px}
    td form{margin:0}
  </style>
</head>
<body class="admin">
<div class="container">
  <header>
    <h1>Skills & Categories</h1>
    <p class="sub">Manage the pre-defined list of categories and specific skills.</p>
    <p><a href="/admin/dashboard.php">← Back to Dashboard</a></p>
  </header>

  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

  <section class="card">
    <h2 style="margin:0 0 10px">Add Skill</h2>
    <form method="post" class="bar">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <input type="hidden" name="add" value="1">
      <input name="category" placeholder="Category (e.g., Academic, Tech Support)" required style="min-width:220px">
      <input name="name" placeholder="Skill (e.g., COMP2030 Tutoring)" required style="min-width:260px">
      <input name="details" placeholder="<?= $detailsCol === 'topics' ? 'Topics (comma-separated)' : 'Description' ?>" style="min-width:260px">
      <button type="submit" class="btn">Add Skill</button>
    </form>
  </section>

  <section class="card">
    <div class="table-wrap">
      <table class="zebra compact">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th style="width:220px">Category</th>
            <th style="width:260px">Skill</th>
            <th><?= $detailsCol === 'topics' ? 'Topics' : 'Description' ?></th>
            <th style="width:220px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="empty">No skills yet. Use the form above to add one.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['skill_id'] ?></td>
              <td>
                <form method="post" class="inline-form">
                  <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                  <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
                  <input name="category" value="<?= h($r['category'] ?? '') ?>" class="in-cell">
              </td>
              <td><input name="name" value="<?= h($r['name'] ?? '') ?>" class="in-cell wide"></td>
              <td><input name="details" value="<?= h($r['details'] ?? '') ?>" class="in-cell wide"></td>
              <td class="actions">
                <button name="update" value="1" class="btn sm">Save</button>
                </form>

                <form method="post" onsubmit="return confirm('Delete this skill?');" class="inline-form">
                  <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                  <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
                  <button name="delete" value="1" class="btn sm danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>
</body>
</html>
