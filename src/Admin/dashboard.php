<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin • Dashboard</title></head>
<body>
<h1>Admin Dashboard</h1>
<?php
// initialize so they always exist
$activeStudents = 0;
$activeServices = 0;
$avgCredits     = 0.0;
$popular        = [];

// --- Active students ---
if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE active=1")) {
    $row = mysqli_fetch_assoc($res);
    $activeStudents = (int)$row['c'];
}

// --- Active services (pending or confirmed transactions) ---
if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM transactions WHERE status IN ('pending','confirmed')")) {
    $row = mysqli_fetch_assoc($res);
    $activeServices = (int)$row['c'];
}

// --- Average FUSS credits ---
if ($res = mysqli_query($conn, "SELECT ROUND(AVG(fuss_credits),2) AS avgc FROM students")) {
    $row = mysqli_fetch_assoc($res);
    $avgCredits = (float)$row['avgc'];
}

// --- Popular skills (top 3 offered) ---
$sql = "
  SELECT s.name, COUNT(*) AS total
  FROM student_skills ss
  JOIN skills s ON s.skill_id = ss.skill_id
  WHERE ss.role='offered'
  GROUP BY s.name
  ORDER BY total DESC
  LIMIT 3
";
if ($res = mysqli_query($conn, $sql)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $popular[] = $row['name'];
    }
}
?>
<ul>
  <li><strong>Active Students:</strong> <?= $activeStudents ?></li>
  <li><strong>Active Services:</strong> <?= $activeServices ?></li>
  <li><strong>Ave FUSSCredits (approx dist):</strong> <?= number_format($avgCredits, 2) ?></li>
  <li><strong>Most Popular Skill:</strong> 
      <?= count($popular) ? htmlspecialchars(implode(', ', $popular)) : '—' ?>
  </li>
</ul>

<nav>
  <a href="students.php">Student Management</a> |
  <a href="credits.php">Credit Adjustment</a> |
  <a href="skills.php">Skills & Categories</a> |
  <a href="moderation.php">Content Moderation</a>
</nav>
</body>
</html>
