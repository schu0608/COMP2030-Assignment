<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html><html><body><h1>Content Moderation</h1></body></html>
<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php
//// Example assumes a simple table:
// flagged_content(id INT PK, content TEXT, type VARCHAR(32), reported_by INT, created_at DATETIME)
if (isset($_GET['remove'])) {
  $id = (int)$_GET['remove'];
  // Option 1: delete the flagged item record
  mysqli_query($conn, "DELETE FROM flagged_content WHERE id=$id");
  echo "<p>Removed flagged item #".htmlspecialchars($id)."</p>";
}

$res = mysqli_query($conn, "SELECT id, type, content, reported_by, created_at FROM flagged_content ORDER BY created_at DESC");
?>

<table border="1" cellpadding="8" cellspacing="0">
  <tr>
    <th>ID</th><th>Type</th><th>Content</th><th>Reported By</th><th>When</th><th>Action</th>
  </tr>
  <?php while($r = $res && mysqli_fetch_assoc($res)): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['type']) ?></td>
      <td><?= nl2br(htmlspecialchars($r['content'])) ?></td>
      <td><?= (int)$r['reported_by'] ?></td>
      <td><?= htmlspecialchars($r['created_at']) ?></td>
      <td><a href="?remove=<?= (int)$r['id'] ?>" onclick="return confirm('Remove this content?')">Remove</a></td>
    </tr>
  <?php endwhile; ?>
</table>
</body></html>
