<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html><html><body><h1>Credit Adjustment</h1></body></html>
<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_PST['student_id'];
    $amount = (int)$_POST['amount'];

    $curRES = mysqli_query($conn, "SELECT credits FROM students WHERE id=$id");
    if ($curRES && $curRow = mysqli_fetch_assoc($curRES)) {
        $new = max(0, (int)$curRow['credits'] + $amount); // no negative balence
        $stmt = mysqli_prepare($conn, "UPDATE students SET credits=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ii", $new, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $msg = "Updated student #$id credits to $new.";
    } else {
        $msg = "Student ID not found.";
    }
}
?>
<?php if ($msg) { echo "<p>".htmlspecialchars($msg)."</p>"; } ?>
<form method="post">
    <label>Student ID: 
        <select name="student_id" required>
            <option value="">-- choose --</option>
            <?php
            $students = mysqli_query($conn, "SELECT id, name, credits FROM students ORDER BY name");
                  while($s = $students && mysqli_fetch_assoc($students)):
      ?>
        <option value="<?= (int)$s['id'] ?>">
          <?= htmlspecialchars($s['name']) ?> (current: <?= (int)$s['credits'] ?>)
        </option>
      <?php endwhile; ?>
    </select>
  </label>
  <label>Amount (+/-):
    <input type="number" name="amount" required>
  </label>
  <button type="submit">Apply</button>
</form>
</body></html>
                
