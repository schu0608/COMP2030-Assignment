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
function field($k,$d=null){ return $_POST[$k] ?? $d; }
$msg = "";

$zones = [];
$zr = mysqli_query($conn, "SELECT zone_id, name FROM zones ORDER BY name");
if ($zr) { while ($z = mysqli_fetch_assoc($zr)) $zones[] = $z; }


/* ----------------------- CREATE ----------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  $zone_id = isset($_POST['zone_id']) && $_POST['zone_id'] !== '' ? (int)$_POST['zone_id'] : null;

  $email = trim((string)field('email',''));
  $full  = trim((string)field('full_name',''));
  $cred  = max(0.0,(float)field('fuss_credits',0));
  $active= isset($_POST['active']) ? 1 : 0;

  if ($email !== '' && $full !== '') {
    // set a temporary password (hashed) – admin can reset later
    $hash = password_hash('Temp123!', PASSWORD_DEFAULT);

    $st = $pdo->prepare("
      INSERT INTO students (email, password, full_name, fuss_credits, active)
      VALUES (?, ?, ?, ?, ?)
    ");
    try {
      $ok = $st->execute([$email, $hash, $full, $cred, $active]);
      $msg = $ok ? "Created student: ".h($full) : "Create failed.";
    } catch (PDOException $e) {
      // Handle duplicate email etc.
      $msg = "Create failed: ".h($e->getMessage());
    }
  } else {
    $msg = "Email and Full name are required.";
  }
}

/* ----------------------- UPDATE ----------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update'])) {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  $zone_id = isset($_POST['zone_id']) && $_POST['zone_id'] !== '' ? (int)$_POST['zone_id'] : null;

$stmt = mysqli_prepare($conn,
  "UPDATE students SET email=?, full_name=?, fuss_credits=?, active=?, zone_id=? WHERE student_id=?");
mysqli_stmt_bind_param($stmt, "ssdi ii", $email,$full,$cred,$active,$zone_id,$id); 


  $id    = (int)field('student_id',0);
  $email = trim((string)field('email',''));
  $full  = trim((string)field('full_name',''));
  $cred  = max(0.0,(float)field('fuss_credits',0));
  $active= isset($_POST['active']) ? 1 : 0;

  if ($id > 0 && $email !== '' && $full !== ''){
    $st = $pdo->prepare("
      UPDATE students SET email=?, full_name=?, fuss_credits=?, active=? WHERE student_id=?
    ");
    try {
      $ok = $st->execute([$email,$full,$cred,$active,$id]);
      $msg = $ok ? "Updated student #$id" : "Update failed.";
    } catch (PDOException $e) {
      $msg = "Update failed: ".h($e->getMessage());
    }
  } else {
    $msg = "Missing required fields for update.";
  }
}

/* ----------------------- TOGGLE ACTIVE ----------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_active'])) {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  $id = (int)field('student_id',0);
  $to = (int)field('to',0);
  if ($id > 0){
    $st = $pdo->prepare("UPDATE students SET active=? WHERE student_id=?");
    $ok = $st->execute([$to,$id]);
    $msg = $ok ? (($to?'Reactivated':'Suspended')." student #$id") : "Toggle failed.";
  } else {
    $msg = "Invalid student id.";
  }
}

/* ----------------------- DELETE ----------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete'])) {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  $id = (int)field('student_id',0);
  if ($id > 0){
    $st = $pdo->prepare("DELETE FROM students WHERE student_id=?");
    try {
      $ok = $st->execute([$id]);
      $msg = $ok ? "Deleted student #$id" : "Delete failed.";
    } catch (PDOException $e) {
      $msg = "Delete failed: ".h($e->getMessage());
    }
  } else {
    $msg = "Invalid student id.";
  }
}

/* ----------------------- FETCH LIST ----------------------- */
$rows = [];
try {
  $rows = $pdo->query("
    SELECT student_id, full_name, email, fuss_credits, active
    FROM students
    ORDER BY student_id ASC
  ")->fetchAll();
} catch (Throwable $e) {
  $rows = [];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Students</title>
  <style>
    :root{
      --bg:#0f172a; --card:#111827; --mut:#94a3b8; --text:#e5e7eb;
      --accent:#FFCC00; --ring:#1f2937; --border:#1f2937; --danger:#ef4444;
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
    .in-cell.short{width:120px}

    .btn{padding:10px 14px; border-radius:10px; border:1px solid var(--ring); background:#111827; color:#e5e7eb; cursor:pointer}
    .btn:hover{background:#0f172a}
    .btn.sm{padding:6px 10px; font-size:.9rem}
    .btn.danger{background:var(--danger); border-color:#b91c1c; color:#0b1220}
    .btn.danger:hover{background:#dc2626}
    .btn.ghost{background:#0f172a}

    .msg{background:#0b2130; border:1px solid #1f3a4d; color:#dbeafe; padding:10px 12px; border-radius:10px; margin-bottom:12px}

    .table-wrap{overflow:auto}
    table{border-collapse:collapse; width:100%}
    th,td{padding:10px 12px; border-bottom:1px solid var(--ring); text-align:left; vertical-align:top}
    thead th{color:#cbd5e1; font-weight:700}
    .empty{color:#94a3b8; text-align:center}
    .actions{display:flex; gap:8px}
    td form{margin:0}
    .flag{display:flex; align-items:center; gap:8px}
    .flag-text{color:#cbd5e1}
  </style>
</head>
<body class="admin">
<div class="container">
  <header>
    <h1>Student Management</h1>
    <p class="sub">View, add, edit, suspend/reactivate, or delete student accounts.</p>
    <p><a href="/admin/dashboard.php">← Back to Dashboard</a></p>
  </header>

  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

  <!-- Add student -->
  <section class="card">
    <form method="post" class="bar">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>
      <input type="hidden" name="create" value="1">
      <input name="full_name" placeholder="Full name" required style="min-width:220px">
      <input type="email" name="email" placeholder="Email" required style="min-width:260px">
      <input type="number" step="0.01" min="0" name="fuss_credits" placeholder="Credits" value="0" class="in-cell short">
      <label><input type="checkbox" name="active" checked> Active</label>
      <button type="submit" class="btn">Add Student</button>
    </form>
    <p class="sub" style="margin-top:6px">New accounts are created with a temporary password (<code>Temp123!</code>). Ask users to reset their password after first login.</p>
  </section>

<!-- Table -->
<section class="card">
  <div class="table-wrap">
    <table class="zebra compact">
      <thead>
        <tr>
          <th style="width:70px">ID</th>
          <th>Full Name</th>
          <th>Email</th>
          <th style="width:140px">Credits</th>
          <th style="width:160px">Zone</th>
          <th style="width:120px">Active</th>
          <th style="width:280px">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="empty">No students yet. Use the form above to add one.</td></tr>
        <?php else: foreach ($rows as $row): ?>
          <tr>
            <td><?= (int)$row['student_id'] ?></td>
            <td>
              <form method="post" class="inline-form">
                <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
                <input name="full_name" value="<?= h($row['full_name']) ?>" class="in-cell">
            </td>
            <td><input type="email" name="email" value="<?= h($row['email']) ?>" class="in-cell wide"></td>
            <td><input type="number" step="0.01" min="0" name="fuss_credits" value="<?= (float)$row['fuss_credits'] ?>" class="in-cell short"></td>

            <!-- Zone dropdown -->
            <td>
              <select name="zone_id" class="in-cell">
                <option value="">—</option>
                <?php foreach ($zones as $z): ?>
                  <option
                    value="<?= (int)$z['zone_id'] ?>"
                    <?= ((int)($row['zone_id'] ?? 0) === (int)$z['zone_id']) ? 'selected' : '' ?>>
                    <?= h($z['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>

            <td>
              <label class="flag">
                <input type="checkbox" name="active" <?= ((int)$row['active'] ? 'checked' : '') ?>>
                <span class="flag-text"><?= ((int)$row['active'] ? 'Yes' : 'No') ?></span>
              </label>
            </td>
            <td class="actions">
              <button name="update" value="1" class="btn sm">Save</button>
              </form>

              <form method="post" onsubmit="return confirm('Are you sure?');" class="inline-form">
                <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
                <input type="hidden" name="to" value="<?= ((int)$row['active'] ? 0 : 1) ?>">
                <button name="toggle_active" value="1" class="btn sm ghost">
                  <?= ((int)$row['active'] ? 'Suspend' : 'Reactivate') ?>
                </button>
              </form>

              <form method="post" onsubmit="return confirm('Delete this student? This cannot be undone.');" class="inline-form">
                <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                <input type="hidden" name="student_id" value="<?= (int)$row['student_id'] ?>">
                <button name="delete" value="1" class="btn sm danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>
