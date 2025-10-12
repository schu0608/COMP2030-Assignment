<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/skills.php';
require_once __DIR__ . '/../includes/validation.php';

require_login();
$uid = current_user_id();
$role = ($_GET['role'] ?? 'offered') === 'requested' ? 'requested' : 'offered';

$msg=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_validate();
  $action = $_POST['action'] ?? '';
  if ($action==='add') {
    $name = sanitize($_POST['name'] ?? '',100);
    $cat  = sanitize($_POST['category'] ?? '',50);
    $desc = sanitize($_POST['description'] ?? '',1000);
    $det  = sanitize($_POST['details'] ?? '',1000);
    if ($name!=='') { add_student_skill($uid,$name,$cat,$desc,$role,$det); $msg='Saved.'; }
    else $msg='Name required.';
  } elseif ($action==='del') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { delete_student_skill($id,$uid); $msg='Deleted.'; }
  }
}
$skills = get_student_skills($uid,$role);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Skills</title></head><body>
<h1>Skills (<?=htmlspecialchars($role)?>)</h1>
<p>
  <a href="/skills_manage.php?role=offered">Offered</a> |
  <a href="/skills_manage.php?role=requested">Requested</a> |
  <a href="/index.php">Back</a>
</p>
<?php if($msg) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; ?>

<h2>Add New</h2>
<form method="post">
  <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
  <input type="hidden" name="action" value="add">
  <label>Name <input name="name" required placeholder='e.g., "COMP1002 Tutoring", "Moving Furniture"'></label><br>
  <label>Category <input name="category" placeholder='Academic / Tech Support / Life Skills / etc.'></label><br>
  <label>Description<br><textarea name="description" rows="3" cols="60"></textarea></label><br>
  <label>Details (topics/degrees etc.)<br><input name="details" placeholder="e.g., C++, SQL, COMP2030"></label><br>
  <button>Save</button>
</form>

<h2>Your <?=htmlspecialchars($role)?> skills</h2>
<?php if (!$skills): ?>
  <p>No skills yet.</p>
<?php else: ?>
  <ul>
  <?php foreach ($skills as $s): ?>
    <li>
      <strong><?=htmlspecialchars($s['name'])?></strong>
      <?php if ($s['category']) echo ' — '.htmlspecialchars($s['category']); ?><br>
      <em><?=htmlspecialchars($s['details'])?></em><br>
      <small><?=htmlspecialchars($s['description'])?></small><br>
      <form method="post" style="display:inline">
        <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
        <input type="hidden" name="action" value="del">
        <input type="hidden" name="id" value="<?=$s['id']?>">
        <button onclick="return confirm('Delete this?')">Delete</button>
      </form>
    </li><hr>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</body></html>
