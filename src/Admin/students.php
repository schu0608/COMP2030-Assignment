<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Students</title>
  <link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=6">
</head>
<body class="admin">
<div class="container">
  <header style="margin-bottom:12px">
    <h1>Student Management</h1>
    <p class="sub">View, add, edit, suspend/reactivate, or delete student accounts.</p>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
  </header>

<?php
function field($k,$d=null){ return $_POST[$k] ?? $d; }
$msg = "";

/* CREATE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  $email = trim(field('email',''));
  $full  = trim(field('full_name',''));
  $deg   = trim(field('degree',''));
  $coll  = trim(field('college',''));
  $year  = (int)field('academic_year',0);
  $cred  = max(0.0,(float)field('fuss_credits',0));
  $active= isset($_POST['active']) ? 1 : 0;
  $pass  = field('password','x');

  if ($email && $full) {
    $stmt = mysqli_prepare($conn,
      "INSERT INTO students (email,password,full_name,degree,college,academic_year,fuss_credits,active)
       VALUES (?,?,?,?,?,?,?,?)");
    if ($stmt){
      mysqli_stmt_bind_param($stmt,"sssssidi",$email,$pass,$full,$deg,$coll,$year,$cred,$active);
      $ok = mysqli_stmt_execute($stmt);
      $msg = $ok ? "Created student: ".htmlspecialchars($full)
                 : "Create failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      mysqli_stmt_close($stmt);
    } else { $msg = "Create failed (prepare): ".htmlspecialchars(mysqli_error($conn)); }
  } else { $msg = "Email and Full name are required."; }
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
  $id    = (int)field('student_id',0);
  $email = trim(field('email',''));
  $full  = trim(field('full_name',''));
  $cred  = max(0.0,(float)field('fuss_credits',0));
  $active= isset($_POST['active']) ? 1 : 0;

  if ($id && $email && $full){
    $stmt = mysqli_prepare($conn,
      "UPDATE students SET email=?, full_name=?, fuss_credits=?, active=? WHERE student_id=?");
    if ($stmt){
      mysqli_stmt_bind_param($stmt,"ssdii",$email,$full,$cred,$active,$id);
      $ok = mysqli_stmt_execute($stmt);
      $msg = $ok ? "Updated student #$id"
                 : "Update failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      mysqli_stmt_close($stmt);
    } else { $msg = "Update failed (prepare): ".htmlspecialchars(mysqli_error($conn)); }
  } else { $msg = "Missing required fields for update."; }
}

/* TOGGLE ACTIVE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_active'])) {
  $id = (int)field('student_id',0);
  $to = (int)field('to',0);
  if ($id){
    $stmt = mysqli_prepare($conn,"UPDATE students SET active=? WHERE student_id=?");
    if ($stmt){
      mysqli_stmt_bind_param($stmt,"ii",$to,$id);
      $ok = mysqli_stmt_execute($stmt);
      $msg = $ok ? (($to?'Reactivated':'Suspended')." student #$id")
                 : "Toggle failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      mysqli_stmt_close($stmt);
    } else { $msg = "Toggle failed (prepare): ".htmlspecialchars(mysqli_error($conn)); }
  }
}

/* DELETE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  $id = (int)field('student_id',0);
  if ($id){
    $stmt = mysqli_prepare($conn,"DELETE FROM students WHERE student_id=?");
    if ($stmt){
      mysqli_stmt_bind_param($stmt,"i",$id);
      $ok = mysqli_stmt_execute($stmt);
      $msg = $ok ? "Deleted student #$id"
                 : "Delete failed: ".htmlspecialchars(mysqli_stmt_error($stmt));
      mysqli_stmt_close($stmt);
    } else { $msg = "Delete failed (prepare): ".htmlspecialchars(mysqli_error($conn)); }
  }
}
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- Add student card -->
<section class="card" style="margin-bottom:16px">
  <form method="post" class="bar">
    <input type="hidden" name="create" value="1">
    <input name="full_name" placeholder="Full name" required style="min-width:220px">
    <input type="email" name="email" placeholder="Email" required style="min-width:260px">
    <input type="number" step="0.01" min="0" name="fuss_credits" placeholder="Credits" value="0" style="width:120px">
    <label><input type="checkbox" name="active" checked> Active</label>
    <button type="submit" class="btn">Add Student</button>
  </form>
</section>

<!-- Table card -->
<section class="card">
  <div class="table-wrap">
    <table class="zebra compact">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th style="width:140px">Credits</th>
          <th style="width:120px">Active</th>
          <th style="width:280px">Actions</th>
        </tr>
      </thead>
      <tbody>
<?php
$res = mysqli_query($conn,"SELECT student_id, full_name, email, fuss_credits, active
                           FROM students ORDER BY student_id ASC");

if (!$res) {
  echo '<tr><td colspan="6" class="empty">Query failed: '.htmlspecialchars(mysqli_error($conn)).'</td></tr>';
} elseif (mysqli_num_rows($res) === 0) {
  echo '<tr><td colspan="6" class="empty">No students yet. Use the form above to add one.</td></tr>';
} else {
  while ($row = mysqli_fetch_assoc($res)) { ?>
        <tr>
          <td><?= (int)$row['student_id'] ?></td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
              <input name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" class="in-cell">
          </td>
          <td><input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" class="in-cell wide"></td>
          <td><input type="number" step="0.01" min="0" name="fuss_credits" value="<?= (float)$row['fuss_credits'] ?>" class="in-cell short"></td>
          <td>
            <label class="flag">
              <input type="checkbox" name="active" <?= $row['active'] ? 'checked' : '' ?>>
              <span class="flag-text"><?= $row['active'] ? 'Yes' : 'No' ?></span>
            </label>
          </td>
          <td class="actions">
            <button name="update" value="1" class="btn sm">Save</button>
            </form>

            <form method="post" onsubmit="return confirm('Are you sure?');" class="inline-form">
              <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
              <input type="hidden" name="to" value="<?= $row['active'] ? 0 : 1 ?>">
              <button name="toggle_active" value="1" class="btn sm ghost">
                <?= $row['active'] ? 'Suspend' : 'Reactivate' ?>
              </button>
            </form>

            <form method="post" onsubmit="return confirm('Delete this student? This cannot be undone.');" class="inline-form">
              <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
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
