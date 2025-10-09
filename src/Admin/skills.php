<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Skills & Categories</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:20px}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ddd;padding:8px;vertical-align:top}
    th{background:#f7f7f7;text-align:left}
    form.inline{display:inline}
    .msg{padding:8px;background:#eef;border:1px solid #ccd;margin-bottom:12px}
    .stack{display:flex;gap:10px;flex-wrap:wrap}
    .stack input{min-width:220px}
  </style>
</head>
<body>
<h1>Skills & Categories</h1>
<p>Manage the pre-defined list of skill categories (e.g., "Academic", "Tech Support", "Technical", "Practical") and specific skills.</p>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php
$msg = "";

/* Detect if table has 'topics' (older draft) or 'description' (your current schema) */
$detailsCol = 'description';
$colRes = mysqli_query(
  $conn,
  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='skills'
     AND COLUMN_NAME IN ('topics','description')"
);
if ($colRes && ($c = mysqli_fetch_assoc($colRes))) {
  $detailsCol = $c['COLUMN_NAME']; // 'topics' or 'description'
}

/* ADD */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])) {
  $category = trim($_POST['category'] ?? "");
  $name     = trim($_POST['name'] ?? "");   // skill name
  $details  = trim($_POST['details'] ?? ""); // topics/description text

  if ($category && $name) {
    if ($detailsCol === 'topics') {
      $stmt = mysqli_prepare($conn, "INSERT INTO skills (name, category, topics) VALUES (?,?,?)");
    } else {
      $stmt = mysqli_prepare($conn, "INSERT INTO skills (name, category, description) VALUES (?,?,?)");
    }
    mysqli_stmt_bind_param($stmt, "sss", $name, $category, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Added skill: ".htmlspecialchars($name);
  } else {
    $msg = "Category and Skill name are required.";
  }
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
  $id       = (int)($_POST['skill_id'] ?? 0);
  $category = trim($_POST['category'] ?? "");
  $name     = trim($_POST['name'] ?? "");
  $details  = trim($_POST['details'] ?? "");

  if ($id && $category && $name) {
    if ($detailsCol === 'topics') {
      $stmt = mysqli_prepare($conn, "UPDATE skills SET name=?, category=?, topics=? WHERE skill_id=?");
    } else {
      $stmt = mysqli_prepare($conn, "UPDATE skills SET name=?, category=?, description=? WHERE skill_id=?");
    }
    mysqli_stmt_bind_param($stmt, "sssi", $name, $category, $details, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Updated skill #$id";
  } else {
    $msg = "Missing required fields for update.";
  }
}

/* DELETE (POST) */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  $id = (int)($_POST['skill_id'] ?? 0);
  if ($id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM skills WHERE skill_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Deleted skill #$id";
  }
}
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<h2>Add Skill</h2>
<form method="post" class="stack">
  <input type="hidden" name="add" value="1">
  <input name="category" placeholder="Category (e.g., Academic, Tech Support)" required>
  <input name="name" placeholder="Skill (e.g., COMP2030 Tutoring)" required>
  <input name="details" placeholder="<?= $detailsCol==='topics' ? 'Topics (comma-separated)' : 'Description' ?>">
  <button type="submit">Add</button>
</form>

<h2>All Skills</h2>
<table>
  <tr>
    <th>ID</th><th>Category</th><th>Skill</th><th><?= $detailsCol==='topics' ? 'Topics' : 'Description' ?></th><th>Actions</th>
  </tr>
<?php
$res = mysqli_query($conn, "SELECT skill_id, category, name, $detailsCol AS details FROM skills ORDER BY category, name");
while ($r = $res && mysqli_fetch_assoc($res)): ?>
  <tr>
    <td><?= (int)$r['skill_id'] ?></td>
    <td>
      <form method="post" class="inline">
        <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
        <input name="category" value="<?= htmlspecialchars($r['category'] ?? '') ?>">
    </td>
    <td><input name="name" value="<?= htmlspecialchars($r['name'] ?? '') ?>"></td>
    <td><input name="details" value="<?= htmlspecialchars($r['details'] ?? '') ?>"></td>
    <td>
      <button name="update" value="1">Save</button>
      </form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this skill?');">
        <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
        <button name="delete" value="1">Delete</button>
      </form>
    </td>
  </tr>
<?php endwhile; ?>
</table>

</body>
</html>
