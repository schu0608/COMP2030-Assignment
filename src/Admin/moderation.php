<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Content Moderation</title>
  <link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=8">
</head>
<body class="admin">
<div class="container">
  <header style="margin-bottom:12px">
    <h1>Content Moderation</h1>
    <p class="sub">Monitor and remove inappropriate content in profiles, skill descriptions, or messages.</p>
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
  </header>

<?php
$msg = "";

/* Detect whether table uses `content_type` or `type` */
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
  $typeCol = $c['COLUMN_NAME'];
}

/* Single remove */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['remove'])) {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM flagged_content WHERE id=?");
    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $id);
      $ok = mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      $msg = $ok ? "Removed flagged item #$id." : "Remove failed.";
    } else {
      $msg = "Remove failed (prepare): ".htmlspecialchars(mysqli_error($conn));
    }
  } else {
    $msg = "Invalid item id.";
  }
}

/* Optional: bulk remove */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['bulk_remove']) && !empty($_POST['ids'])) {
  $ids = array_map('intval', (array)$_POST['ids']);
  $ids = array_filter($ids, fn($n)=>$n>0);
  if ($ids) {
    $idList = implode(',', $ids);
    $ok = mysqli_query($conn, "DELETE FROM flagged_content WHERE id IN ($idList)");
    $msg = $ok ? "Removed ".count($ids)." item(s)." : "Bulk remove failed: ".htmlspecialchars(mysqli_error($conn));
  } else {
    $msg = "No items selected.";
  }
}

/* Fetch flagged items */
$sql = "SELECT id, $typeCol AS ctype, content, reported_by, created_at 
          FROM flagged_content
      ORDER BY created_at DESC";
$res = mysqli_query($conn, $sql);
?>

<?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

<section class="card">
  <?php if (!$res || mysqli_num_rows($res) === 0): ?>
    <p class="empty">No flagged content right now. 🎉</p>
  <?php else: ?>
  <form method="post">
    <div class="bar" style="justify-content:space-between">
      <div class="sub">Showing <?= number_format(mysqli_num_rows($res)) ?> flagged item(s)</div>
      <div>
        <button name="bulk_remove" value="1" class="btn danger" onclick="return confirm('Remove selected items?')">Remove Selected</button>
      </div>
    </div>

    <div class="table-wrap">
      <table class="zebra compact">
        <thead>
          <tr>
            <th style="width:44px"><input type="checkbox" onclick="document.querySelectorAll('.js-rowchk').forEach(c=>c.checked=this.checked)"></th>
            <th style="width:70px">ID</th>
            <th style="width:160px">Type</th>
            <th>Content</th>
            <th style="width:140px">Reported By</th>
            <th style="width:180px">When</th>
            <th style="width:120px">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while($r = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><input class="js-rowchk" type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['ctype'] ?? '') ?></td>
              <td style="white-space:pre-wrap"><?= htmlspecialchars($r['content'] ?? '') ?></td>
              <td><?= (int)($r['reported_by'] ?? 0) ?></td>
              <td><?= htmlspecialchars($r['created_at'] ?? '') ?></td>
              <td class="actions">
                <form method="post" onsubmit="return confirm('Remove this content?');" class="inline-form">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button name="remove" value="1" class="btn sm danger">Remove</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </form>
  <?php endif; ?>
</section>

</div>
</body>
</html>
