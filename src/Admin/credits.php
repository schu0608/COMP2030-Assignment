<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Credit Adjustment</title>
  <link rel="stylesheet" href="../css/style.css">

  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:20px}
    form label{display:block;margin:8px 0}
  </style>
</head>
<body>
<h1>FUSSCredit Adjustment</h1>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;

    if ($id > 0) {
        $curRes = mysqli_query($conn, "SELECT fuss_credits FROM students WHERE student_id=$id");
        if ($curRes && ($curRow = mysqli_fetch_assoc($curRes))) {
            $current = (float)$curRow['fuss_credits'];
            $new     = max(0.0, $current + $amount); // never below 0

            $stmt = mysqli_prepare($conn, "UPDATE students SET fuss_credits=? WHERE student_id=?");
            if ($stmt) {
              mysqli_stmt_bind_param($stmt, "di", $new, $id);
              mysqli_stmt_execute($stmt);
              mysqli_stmt_close($stmt);
              $msg = "Updated student #$id credits: $current → $new";
            } else {
              $msg = "Update failed: ".mysqli_error($conn);
            }
        } else {
            $msg = "Student not found.";
        }
    } else {
        $msg = "Please choose a student.";
    }
}
?>

<?php if ($msg): ?>
  <p><strong><?= htmlspecialchars($msg) ?></strong></p>
<?php endif; ?>

<form method="post">
  <label>Student:
    <select name="student_id" required>
      <option value="">-- choose --</option>
      <?php
      $students = mysqli_query($conn, "SELECT student_id, full_name, fuss_credits FROM students ORDER BY full_name");
      if ($students) {
        while ($s = mysqli_fetch_assoc($students)): ?>
          <option value="<?= (int)$s['student_id'] ?>">
            <?= htmlspecialchars($s['full_name']) ?> (current: <?= (float)$s['fuss_credits'] ?>)
          </option>
        <?php endwhile;
      } ?>
    </select>
  </label>

  <label>Amount (+/- hours):
    <input type="number" step="0.01" name="amount" required placeholder="+5 or -2">
  </label>

  <button type="submit">Apply</button>
</form>

</body>
</html>
