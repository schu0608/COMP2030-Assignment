<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Students</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:20px}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ddd;padding:8px}
    th{background:#f7f7f7;text-align:left}
    form.inline{display:inline}
    .row-actions form{display:inline;margin-right:6px}
    .top{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    .msg{padding:8px;background:#eef;border:1px solid #ccd;margin-bottom:10px}
  </style>
</head>
<body>
<h1>Student Management</h1>
<p>List of students, with options to edit, deactivate/reactivate, or delete accounts.</p>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php
$msg = "";

/* CREATE (simple) */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  $email = trim($_POST['email'] ?? "");
  $full  = trim($_POST['full_name'] ?? "");
  $cred  = max(0, (float)($_POST['fuss_credits'] ?? 0));
  $active= isset($_POST['active']) ? 1 : 0;
  $pass  = $_POST['password'] ?? 'x'; // placeholder

  if ($email && $full) {
    $stmt = mysqli_prepare($conn,
      "INSERT INTO students (email,password,full_name,fuss_credits,active) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "sssdi", $email,$pass,$full,$cred,$active);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Created student: ".htmlspecialchars($full);
  } else {
    $msg = "Email and Full name are required.";
  }
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
  $id    = (int)($_POST['student_id'] ?? 0);
  $email = trim($_POST['email'] ?? "");
  $full  = trim($_POST['full_name'] ?? "");
  $cred  = max(0, (float)($_POST['fuss_credits'] ?? 0));
  $active= isset($_POST['active']) ? 1 : 0;

  if ($id && $email && $full) {
    $stmt = mysqli_prepare($conn,
      "UPDATE students SET email=?, full_name=?, fuss_credits=?, active=? WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, "ssdii", $email,$full,$cred,$active,$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Updated student #$id";
  } else {
    $msg = "Missing required fields for update.";
  }
}

/* TOGGLE ACTIVE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_active'])) {
  $id = (int)($_POST['student_id'] ?? 0);
  $to = (int)($_POST['to'] ?? 0);
  if ($id) {
    $stmt = mysqli_prepare($conn, "UPDATE students SET active=? WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, "ii", $to,$id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = ($to? "Reactivated":"Suspended")." student #$id";
  }
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  $id = (int)($_POST['student_id'] ?? 0);
  if ($id) {
    $stmt = mysqli_prepare($conn, "DELETE FROM students WHERE student_id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Deleted student #$id";
  }
}
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="top">
  <form method="post">
    <strong>Add student:</strong>
    <input type="hidden" name="create" value="1">
    <input name="full_name" placeholder="Full name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="number" step="0.01" min="0" name="fuss_credits" placeholder="Credits" value="0">
    <label><input type="checkbox" name="active" checked> Active</label>
    <button type="submit">Add</button>
  </form>
</div>

<table>
  <tr>
    <th>ID</th><th>Full Name</th><th>Email</th><th>Credits</th><th>Active</th><th>Actions</th>
  </tr>
<?php
$res = mysqli_query($conn, "SELECT student_id, full_name, email, fuss_credits, active FROM students ORDER BY student_id ASC");
while ($row = $res && mysqli_fetch_assoc($res)): ?>
  <tr>
    <td><?= (int)$row['student_id'] ?></td>
    <td>
      <form method="post" class="inline">
        <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
        <input name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>">
    </td>
    <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>"></td>
    <td><input type="number" step="0.01" min="0" name="fuss_credits" value="<?= (float)$row['fuss_credits'] ?>"></td>
    <td>
      <label><input type="checkbox" name="active" <?= $row['active'] ? "checked" : "" ?>> <?= $row['active']?"Yes":"No" ?></label>
    </td>
    <td class="row-actions">
      <button name="update" value="1">Save</button>
      </form>

      <form method="post" class="inline" onsubmit="return confirm('Are you sure?');">
        <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
        <input type="hidden" name="to" value="<?= $row['active'] ? 0 : 1 ?>">
        <button name="toggle_active" value="1"><?= $row['active'] ? "Suspend" : "Reactivate" ?></button>
      </form>

      <form method="post" class="inline" onsubmit="return confirm('Delete this student? This cannot be undone.');">
        <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
        <button name="delete" value="1">Delete</button>
      </form>
    </td>
  </tr>
<?php endwhile; ?>
</table>

</body>
</html>
