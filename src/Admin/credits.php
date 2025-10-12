<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Credit Adjustment</title>
  <link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=8">
</head>
<body class="admin">
<div class="container">
  <header style="margin-bottom:12px">
    <h1>FUSSCredit Adjustment</h1>
    <p class="sub">Manually add or deduct credits from a student’s balance (no negative balances allowed).</p>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
  </header>

<?php
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id     = (int)($_POST['student_id'] ?? 0);
  $amount = (float)($_POST['amount'] ?? 0);

  if ($id <= 0) {
    $msg = "Please choose a student.";
  } else {
    // fetch current
    $curRes = mysqli_query($conn, "SELECT fuss_credits, full_name FROM students WHERE student_id = $id");
    if ($curRes && ($curRow = mysqli_fetch_assoc($curRes))) {
      $current = (float)$curRow['fuss_credits'];
      $name    = $curRow['full_name'];
      $new     = max(0.0, $current + $amount); // never below 0

      $stmt = mysqli_prepare($conn, "UPDATE students SET fuss_credits=? WHERE student_id=?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, "di", $new, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($ok) {
          // show + or - nicely
          $delta = ($amount >= 0 ? "+" : "") . number_format($amount, 2);
          $msg = "Updated <strong>".htmlspecialchars($name)."</strong> credits: "
               . number_format($current,2) . " → " . number_format($new,2)
               . " (<em>$delta</em>)";
        } else {
          $msg = "Update failed: ".htmlspecialchars(mysqli_error($conn));
        }
      } else {
        $msg = "Update failed (prepare): ".htmlspecialchars(mysqli_error($conn));
      }
    } else {
      $msg = "Student not found.";
    }
  }
}
?>

<?php if ($msg): ?>
  <div class="msg"><?= $msg ?></div>
<?php endif; ?>

<!-- Adjustment form card -->
<section class="card" style="margin-bottom:16px">
  <h2 style="margin:0 0 10px">Adjust Balance</h2>
  <form method="post" class="bar">
    <label for="student_id" style="display:none">Student</label>
    <select name="student_id" id="student_id" required style="min-width:320px;padding:8px;border:1px solid var(--border);border-radius:8px;color:#0f172a">
      <option value="">— choose a student —</option>
      <?php
        $students = mysqli_query($conn, "SELECT student_id, full_name, fuss_credits FROM students ORDER BY full_name");
        if ($students) {
          while ($s = mysqli_fetch_assoc($students)) {
            $sid = (int)$s['student_id'];
            $lbl = $s['full_name']." (current: ".number_format((float)$s['fuss_credits'],2).")";
            echo '<option value="'.$sid.'">'.htmlspecialchars($lbl).'</option>';
          }
        }
      ?>
    </select>

    <label for="amount" style="display:none">Amount</label>
    <input id="amount" type="number" step="0.01" name="amount" required placeholder="+5 or -2" class="in-cell short" style="width:140px">

    <button type="submit" class="btn">Apply</button>
  </form>
  <p class="sub" style="margin-top:6px">Tip: use positive numbers to add credits, negative numbers to deduct.</p>
</section>

<!-- Balances table card -->
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
        <?php
        $res = mysqli_query($conn, "SELECT student_id, full_name, fuss_credits, active FROM students ORDER BY full_name");
        if (!$res) {
          echo '<tr><td colspan="4" class="empty">Query failed: '.htmlspecialchars(mysqli_error($conn)).'</td></tr>';
        } elseif (mysqli_num_rows($res) === 0) {
          echo '<tr><td colspan="4" class="empty">No students yet.</td></tr>';
        } else {
          while ($r = mysqli_fetch_assoc($res)) { ?>
            <tr>
              <td><?= (int)$r['student_id'] ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= number_format((float)$r['fuss_credits'], 2) ?></td>
              <td><?= $r['active'] ? 'Yes' : 'No' ?></td>
            </tr>
        <?php } } ?>
      </tbody>
    </table>
  </div>
</section>

</div>
</body>
</html>
