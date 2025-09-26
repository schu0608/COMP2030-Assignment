<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html><html><body><h1>Skills & Categories</h1></body></html>
<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php
// handle actions (add, edit, delete skills/categories) - left as an exercise
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ISSET($_PST['add'])) {
    $catergory = trim($_POST['category']);
    $skill = trim($_POST['skill']);
    $topics = trim($_POST['topics']); // comma separated
    $stmt = mysqli_prepare($conn, "UPDATE skills SEt catergory=?, skill=?, topics=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sssi", $catergory, $skill, $topics, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo "<p>Added skill ".htmlspecialchars($skill). "</p>";

    //handle update 
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ISSET($_PST['update'])) {
        $id = (int)$_POST['id'];
        $catergory = trim($_POST['category']);
        $skill = trim($_POST['skill']);
        $topics = trim($_POST['topics']); // comma separated
        $stmt = mysqli_prepare($conn, "UPDATE skills SEt catergory=?, skill=?, topics=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssi", $catergory, $skill, $topics, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo "<p>Updated skill #".htmlspecialchars($id). "</p>";
    }
    //handle delete
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        mysqli_query($conn, "DELETE FROM skills WHERE id=$id");
        echo "<p>Deleted skill #".htmlspecialchars($id). "</p>";
    }   

    ?>

<h2>Add Skill</h2>
<form method="post">
  <input name="category" placeholder="Category (e.g., Academic, Tech Support)" required>
  <input name="skill" placeholder="Skill (e.g., COMP2030 Tutoring)" required>
  <input name="topics" placeholder="Topics (comma-separated)">
  <button name="add" value="1">Add</button>
</form>

<h2>All Skills</h2>
<table border="1" cellpadding="8" cellspacing="0">
  <tr><th>ID</th><th>Category</th><th>Skill</th><th>Topics</th><th>Actions</th></tr>
  <?php
  $res = mysqli_query($conn, "SELECT id, category, skill, topics FROM skills ORDER BY category, skill");
  while($r = $res && mysqli_fetch_assoc($res)): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td>
        <form method="post" style="display:inline">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input name="category" value="<?= htmlspecialchars($r['category']) ?>">
      </td>
      <td><input name="skill" value="<?= htmlspecialchars($r['skill']) ?>"></td>
      <td><input name="topics" value="<?= htmlspecialchars($r['topics']) ?>"></td>
      <td>
          <button name="update" value="1">Save</button>
          <a href="?delete=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this skill?')">Delete</a>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
</body></html>
<?php
}