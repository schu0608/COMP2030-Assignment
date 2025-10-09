<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Skills & Categories</title>
  <link rel="stylesheet" href="../css/style.css">

  <style>
    :root { --b:#e5e7eb; --bg:#f9fafb; --fg:#111827; --mut:#6b7280; }
    body{font-family:system-ui,Arial,sans-serif;margin:24px;color:var(--fg);background:var(--bg)}
    h1{margin:0 0 4px} .sub{color:var(--mut);margin:0 0 16px}
    a{color:#6c2bd9;text-decoration:none} a:hover{text-decoration:underline}
    .msg{padding:10px 12px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:10px;margin:10px 0}
    .stack{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px}
    .stack input, .stack button{padding:8px;border:1px solid var(--b);border-radius:8px}
    table{border-collapse:collapse;width:100%;background:#fff;border:1px solid var(--b);border-radius:12px;overflow:hidden}
    th,td{border-top:1px solid var(--b);padding:10px 12px;vertical-align:middle}
    th{background:#f3f4f6;text-align:left}
    td form{display:inline} .actions form{margin-right:6px}
    .empty{padding:24px;color:var(--mut)}
  </style>
</head>
<body>
<h1>Skills & Categories</h1>
<p class="sub">Manage the pre-defined list of categories and specific skills.</p>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php
$msg = "";

/* Detect details column (description vs topics) */
$detailsCol = 'description';
$colRes = mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='skills'
                                 AND COLUMN_NAME IN ('description','topics')");
if ($colRes && ($c = mysqli_fetch_assoc($colRes))) $detailsCol = $c['COLUMN_NAME'];

/* ADD */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])) {
  $category = trim($_POST['category'] ?? "");
  $name     = trim($_POST['name'] ?? "");
  $details  = trim($_POST['details'] ?? "");
  if ($category && $name) {
    $sql  = $detailsCol==='topics'
          ? "INSERT INTO skills (name,category,topics) VALUES (?,?,?)"
          : "INSERT INTO skills (name,category,description) VALUES (?,?,?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { $msg = "Add failed: ".mysqli_error($conn); }
    else {
      mysqli_stmt_bind_param($stmt,"sss",$name,$category,$details);
      mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
      $msg = "Added skill: ".htmlspecialchars($name);
    }
  } else { $msg = "Category and Skill are required."; }
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
  $id       = (int)($_POST['skill_id'] ?? 0);
  $category = trim($_POST['category'] ?? "");
  $name     = trim($_POST['name'] ?? "");
  $details  = trim($_POST['details'] ?? "");
  if ($id && $category && $name) {
    $sql  = $detailsCol==='topics'
          ? "UPDATE skills SET name=?, category=?, topics=? WHERE skill_id=?"
          : "UPDATE skills SET name=?, category=?, description=? WHERE skill_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) { $msg = "Update failed: ".mysqli_error($conn); }
    else {
      mysqli_stmt_bind_param($stmt,"sssi",$name,$category,$details,$id);
      mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
      $msg = "Updated skill #$id";
    }
  } else { $msg = "Missing required fields for update."; }
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  $id = (int)($_POST['skill_id'] ?? 0);
  $stmt = mysqli_prepare($conn, "DELETE FROM skills WHERE skill_id=?");
  if (!$stmt) { $msg = "Delete failed: ".mysqli_error($conn); }
  else { mysqli_stmt_bind_param($stmt,"i",$id); mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    $msg = "Deleted skill #$id"; }
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
    <th style="width:70px">ID</th>
    <th style="width:220px">Category</th>
    <th style="width:260px">Skill</th>
    <th><?= $detailsCol==='topics' ? 'Topics' : 'Description' ?></th>
    <th style="width:200px">Actions</th>
  </tr>
<?php
$res = mysqli_query($conn, "SELECT skill_id, category, name, $detailsCol AS details FROM skills ORDER BY category, name");

if (!$res) {
  echo '<tr><td colspan="5" class="empty">Query failed: '.htmlspecialchars(mysqli_error($conn)).'</td></tr>';
} elseif (mysqli_num_rows($res) === 0) {
  echo '<tr><td colspan="5" class="empty">No skills yet. Use the form above to add one.</td></tr>';
} else {
  while ($r = mysqli_fetch_assoc($res)) { ?>
    <tr>
      <td><?= (int)$r['skill_id'] ?></td>
      <td>
        <form method="post">
          <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
          <input name="category" value="<?= htmlspecialchars($r['category'] ?? '') ?>">
      </td>
      <td><input name="name" value="<?= htmlspecialchars($r['name'] ?? '') ?>"></td>
      <td><input name="details" value="<?= htmlspecialchars($r['details'] ?? '') ?>"></td>
      <td class="actions">
        <button name="update" value="1">Save</button>
        </form>
        <form method="post" onsubmit="return confirm('Delete this skill?');">
          <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
          <button name="delete" value="1">Delete</button>
        </form>
      </td>
    </tr>
<?php } } ?>
</table>

</body>
</html>
