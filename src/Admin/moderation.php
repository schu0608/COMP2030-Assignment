<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Content Moderation</title>
  <style>
    body{font-family:system-ui,Arial,sans-serif;margin:20px}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #ddd;padding:8px;vertical-align:top}
    th{background:#f7f7f7;text-align:left}
    .msg{padding:8px;background:#eef;border:1px solid #ccd;margin-bottom:12px}
    .actions form{display:inline}
  </style>
</head>
<body>
<h1>Content Moderation</h1>
<p>Monitor and remove inappropriate content in profiles, skill descriptions, or messages.</p>
<p><a href="dashboard.php">← Back to Dashboard</a></p>

<?php
$msg = "";

/* Detect whether the table uses `content_type` or `type` */
$typeCol = 'content_type';
$colRes = mysqli_query(
  $conn,
  "SELECT COLUMN_NAME 
     FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'flagged_content' 
      AND COLUMN_NAME IN ('content_type','type')"
);
if ($colRes && ($c = mysqli_fetch_assoc($colRes))) {
  $typeCol = $c['COLUMN_NAME']; // 'content_type' or 'type'
}

/* Handle delete via POST */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['remove'])) {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM flagged_content WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = "Removed flagged item #$id.";
  } else {
    $msg = "Invalid item id.";
  }
}

/* Fetch flagged items */
$sql = "SELECT id, $typeCol AS ctype, content, reported_by, created_at 
          FROM flagged_content
      ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<?php if (!$res || mysqli_num_rows($res) === 0): ?>
  <p>No flagged content right now. 🎉</p>
<?php else: ?>
<table>
  <tr>
    <th>ID</th>
    <th>Type</th>
    <th>Content</th>
    <th>Reported By</th>
    <th>When</th>
    <th>Action</th>
  </tr>
  <?php while($r = mysqli_fetch_assoc($res)): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['ctype'] ?? '') ?></td>
      <td><?= nl2br(htmlspecialchars($r['content'] ?? '')) ?></td>
      <td><?= (int)($r['reported_by'] ?? 0) ?></td>
      <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
      <td class="actions">
        <form method="post" onsubmit="return confirm('Remove this content?');">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button name="remove" value="1">Remove</button>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
</table>
<?php endif; ?>

</body>
</html>
