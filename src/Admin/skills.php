<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Skills & Categories</title>
  <link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=7">
</head>
<body class="admin">
<div class="container">
  <header style="margin-bottom:12px">
    <h1>Skills & Categories</h1>
    <p class="sub">Manage the pre-defined list of categories and specific skills.</p>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
  </header>

<?php
$msg = "";

/* Detect details column (description vs topics) */
$detailsCol = 'description';
$colRes = mysqli_query(
  $conn,
  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='skills'
     AND COLUMN_NAME IN ('description','topics')"
);
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
    if (!$stmt) {
      $msg = "Add failed: ".mysqli_error($conn);
    } else {
      mysqli_stmt_bind_param($stmt,"sss",$name,$category,$details);
      if (mysqli_stmt_execute($stmt)) {
        $msg = "Added skill: ".htmlspecialchars($name);
      } else {
        $msg = "Add failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      }
      mysqli_stmt_close($stmt);
    }
  } else {
    $msg = "Category and Skill are required.";
  }
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
    if (!$stmt) {
      $msg = "Update failed (prepare): ".mysqli_error($conn);
    } else {
      mysqli_stmt_bind_param($stmt,"sssi",$name,$category,$details,$id);
      if (mysqli_stmt_execute($stmt)) {
        $msg = "Updated skill #$id";
      } else {
        $msg = "Update failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      }
      mysqli_stmt_close($stmt);
    }
  } else {
    $msg = "Missing required fields for update.";
  }
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  $id = (int)($_POST['skill_id'] ?? 0);
  $stmt = mysqli_prepare($conn, "DELETE FROM skills WHERE skill_id=?");
  if (!$stmt) {
    $msg = "Delete failed (prepare): ".mysqli_error($conn);
  } else {
    mysqli_stmt_bind_param($stmt,"i",$id);
    if (mysqli_stmt_execute($stmt)) {
      $msg = "Deleted skill #$id";
    } else {
      $msg = "Delete failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
  }
}
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Add skill card -->
<section class="card" style="margin-bottom:16px">
  <h2 style="margin:0 0 10px">Add Skill</h2>
  <form method="post" class="bar">
    <input type="hidden" name="add" value="1">
    <input name="category" placeholder="Category (e.g., Academic, Tech Support)" required style="min-width:220px">
    <input name="name" placeholder="Skill (e.g., COMP2030 Tutoring)" required style="min-width:260px">
    <input name="details" placeholder="<?= $detailsCol==='topics' ? 'Topics (comma-separated)' : 'Description' ?>" style="min-width:260px">
    <button type="submit" class="btn">Add Skill</button>
  </form>
</section>

<!-- Skills table card -->
<section class="card">
  <div class="table-wrap">
    <table class="zebra compact">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th style="width:220px">Category</th>
          <th style="width:260px">Skill</th>
          <th><?= $detailsCol==='topics' ? 'Topics' : 'Description' ?></th>
          <th style="width:220px">Actions</th>
        </tr>
      </thead>
      <tbody>
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
            <form method="post" class="inline-form">
              <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
              <input name="category" value="<?= htmlspecialchars($r['category'] ?? '') ?>" class="in-cell">
          </td>
          <td><input name="name" value="<?= htmlspecialchars($r['name'] ?? '') ?>" class="in-cell wide"></td>
          <td><input name="details" value="<?= htmlspecialchars($r['details'] ?? '') ?>" class="in-cell wide"></td>
          <td class="actions">
            <button name="update" value="1" class="btn sm">Save</button>
            </form>

            <form method="post" onsubmit="return confirm('Delete this skill?');" class="inline-form">
              <input type="hidden" name="skill_id" value="<?= (int)$r['skill_id'] ?>">
              <button name="delete" value="1" class="btn sm danger">Delete</button>
            </form>
          </td>
        </tr>
<?php } } ?>
      </tbody>
    </table>
  </div>
</section>

</div>
</body>
</html>
