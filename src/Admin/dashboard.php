<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html><body>
<h1>Admin Dashboard</h1>

<?php
// the active students 
$activeStudents = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE active=1");
if ($res) { $activeStudents = (int)mysqli_fetch_assoc($res)['c'];}

// active services (adjust/filter to mathch schema)
$activeServices = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM services WHERE status='active'");
if ($res) { $activeServices = (int)mysqli_fetch_assoc($res)['c'];}

//average credits (quick look at distribution)
$avgCredits = 0;
$res = mysqli_query($conn, "SELECT skill, COUNT(*) AS total FROM skills GROUP BY skill ORDER BY total DESC LIMIT 1");
if ($res) { while($row = mysqli_fetch_assoc($res)) { // $popular[] = $row;['skill']; } }
    $avgCredits = $row['skill'] . " ({$row['total']} entries)";
}}
?>

<ul>
    <li><strong>Active Students:</strong> <?php echo $activeStudents; ?></li>
    <li><strong>Active Services:</strong> <?php echo $activeServices; ?></li>
    <li><strong>Ave FUSSCredits (approx dist):</strong> <?= $aveCredit ?></li>
    <li><strong>Most Popular Skill:</strong> <?= htmlspecialchars(implode(", ", $popular)); ?></li>
</ul>

  <nav>
  <li><a href="/admin/students.php">Student Management</a></li>
  <li><a href="/admin/credits.php">Credit Adjustment</a></li>
  <li><a href="/admin/skills.php">Skill & Category Management</a></li>
  <li><a href="/admin/moderation.php">Content Moderation</a></li>
  </nav>
</body></html>
