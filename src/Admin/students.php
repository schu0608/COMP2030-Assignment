<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<!doctype html><html><head>?<meta charset="utf-8"><title>Admin • Students</title></head><body> 
<h1>Student Management</h1>
<p>List of students, with options to edit, deactivate/reactivate, or delete accounts.</p>

<?php
// handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id =(int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $credits = (int)($_POST['credits']);
    $active = isset($_POST['active']) ? 1 : 0;

    $smt = mysqli_prepare($conn, "UPDATE students SET name=?, email=?, credits=?, active=? WHERE id=?");
    mysqli_stmt_bind_param($smt, "ssiii", $name, $email, $credits, $active, $id);
    mysqli_stmt_execute($smt);
    mysqli_stmt_close($smt);
    echo "<p>Updated student #".htmlspecialchars($id). "</p>";
}

if (isset($_GET[['sus[pend']])) {
    $id = (int)$_GET['suspend'];
    mysqli_query($conn, "UPDATE students SET active=0 WHERE id=$id");
    echo "<p>Suspended student #".htmlspecialchars($id). "</p>";
}

if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM students WHERE id=$id");
    echo "<p>Deleted student #".htmlspecialchars($id). "</p>";
}

//fetch list 
$res = mysqli_query($conn, "SELECT id, name, email, credits, active FROM students ORDER BY id ASC");
?>

<table border="1" cellpadding="5" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Credits</th><th>Active</th><th>Actions</th></tr>
<?php while($row = mysqli_fetch_assoc($res)) { ?>
    <tr>
        <form method="post" style="display:inline;">
            <td><?= (int)$row['id'] ?></td>
            <td>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>">
            </td>
            <td>
                <input name="email" value="<?= htmlspecialchars($row['email']) ?>">
            </td>
            <td>
                <input type="number" name="credits" value="<?= (int)$row['credits'] ?>" min="0">
            </td>
            <td>
                <input type="checkbox" name="active" <?= $row['active'] ? 'checked' : '' ?>>
            </td>
            <td>
                <button name="update" value="1">Save</button>
                <?php if ($row['active']) { ?>
                    <a href="?suspend=<?= (int)$row['id'] ?>" onclick="return confirm('Suspend this student?');">Suspend</a>
                <?php } else { ?>
                    <a href="?suspend=<?= (int)$row['id'] ?>" onclick="return confirm('Reactivate this student?');">Reactivate</a>
                <?php } ?>
                <a href="?delete=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this student? This action cannot be undone.');">Delete</a>
            </td>
        </form>
    </tr>
<?php } ?>
</table>
</body></html>
