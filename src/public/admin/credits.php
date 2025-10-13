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
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (function_exists('validate_csrf')) { validate_csrf(); }

  $id     = (int)($_POST['student_id'] ?? 0);
  $amount = (float)($_POST['amount'] ?? 0);

  if ($id <= 0) {
    $msg = 'Please choose a student.';
  } else {
    // fetch current balance + name
    $st = $pdo->prepare('SELECT fuss_credits, full_name FROM students WHERE student_id=?');
    $st->execute([$id]);
    $row = $st->fetch();

    if (!$row) {
      $msg = 'Student not found.';
    } else {
      $current = (float)$row['fuss_credits'];
      $name    = (string)$row['full_name'];
      $new     = $current + $amount;
      if ($new < 0) $new = 0.0;                // never below 0

      $up = $pdo->prepare('UPDATE students SET fuss_credits=? WHERE student_id=?');
      if ($up->execute([$new, $id])) {
        $delta = ($amount >= 0 ? '+' : '') . number_format($amount, 2);
        $msg   = 'Updated <strong>'.h($name).'</strong> credits: '
               . number_format($current,2) . ' → ' . number_format($new,2)
               . ' (<em>'.$delta.'</em>)';
      } else {
        $msg = 'Update failed.';
      }
    }
  }
}

// pull list of students for the select + table
$students = $pdo->query('SELECT student_id, full_name, fuss_credits, active FROM students ORDER BY full_name')->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Credit Adjustment</title>
  <style>
    :root{
      --bg:#0f172a; --card:#111827; --mut:#94a3b8; --text:#e5e7eb;
      --accent:#FFCC00; --ring:#1f2937; --chip:#1f2937; --border:#1f2937;
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
    select,input[type=number]{padding:10px; border-radius:10px; border:1px solid var(--border); background:#0f172a; color:#e5e7eb}
    input[type=number]{width:160px}
    .btn{padding:10px 14px; border-radius:10px; border:1px solid var(--ring); background:#111827; color:#e5e7eb; cursor:pointer}
    .btn:hover{background:#0f172a}

    .msg{background:#0b2130; border:1px solid #1f3a4d; color:#dbeafe; padding:10px 12px; border-radius:10px; margin-bottom:12px}

    .table-wrap{overflow:auto}
    table{border-collapse:collapse; width:100%}
    th,td{padding:10px 12px; border-bottom:1px solid var(--ring); text-align:left}
    thead th{color:#cbd5e1; font-weight:700}
    .empty{color:#94a3b8; text-align:center}
  </style>
</head>
<body class="admin">
<div class="container">

  <header>
    <h1>FUSSCredit Adjustment</h1>
    <p class="sub">Manually add or deduct credits from a student’s balance (no negative balances allowed).</p>
    <p><a href="/admin/dashboard.php">← Back to Dashboard</a></p>
  </header>

  <?php if ($msg): ?>
    <div class="msg"><?= $msg ?></div>
  <?php endif; ?>

  <!-- Adjustment form -->
  <section class="card">
    <h2 style="margin:0 0 10px">Adjust Balance</h2>
    <form method="post" class="bar">
      <?= function_exists('csrf_field') ? csrf_field() : '' ?>

      <label for="student_id" style="display:none">Student</label>
      <select name="student_id" id="student_id" required>
        <option value="">— choose a student —</option>
        <?php foreach ($students as $s): ?>
          <?php
            $sid = (int)$s['student_id'];
            $lbl = $s['full_name'].' (current: '.number_format((float)$s['fuss_credits'],2).')';
          ?>
          <option value="<?= $sid ?>"><?= h($lbl) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="amount" style="display:none">Amount</label>
      <input id="amount" type="number" step="0.01" name="amount" required placeholder="+5 or -2">
      <button type="submit" class="btn">Apply</button>
    </form>
    <p class="sub" style="margin-top:6px">Tip: use positive numbers to add credits, negative numbers to deduct. Balances never go below 0.</p>
  </section>

  <!-- Balances table -->
  <section class="card">
    <h2 style="margin:0 0 10px">Current Balances</h2>
    <div class="table-wrap">
      <table class="zebra compact">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th>Student</th>
            <th style="width:160px">Credits</th>
            <th style="width:140px">Active</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$students): ?>
            <tr><td colspan="4" class="empty">No students yet.</td></tr>
          <?php else: foreach ($students as $r): ?>
            <tr>
              <td><?= (int)$r['student_id'] ?></td>
              <td><?= h($r['full_name']) ?></td>
              <td><?= number_format((float)$r['fuss_credits'], 2) ?></td>
              <td><?= ((int)$r['active'] ? 'Yes' : 'No') ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>
</body>
</html>
