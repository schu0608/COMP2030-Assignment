<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

require_login();
$uid = current_user_id(); $msg=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_validate();
  $full_name = sanitize($_POST['full_name'] ?? '',100);
  $degree    = sanitize($_POST['degree'] ?? '',100);
  $college   = sanitize($_POST['college'] ?? '',100);
  $year      = max(1, min(12, (int)($_POST['academic_year'] ?? 1)));
  $bio       = sanitize($_POST['bio'] ?? '', 1000);
  $picName   = upload_image($_FILES['profile_picture'] ?? null);

  $sql = "UPDATE students SET full_name=?, degree=?, college=?, academic_year=?, bio=?"
       . ($picName ? ", profile_picture=?" : "")
       . " WHERE student_id=?";
  $params = [$full_name,$degree,$college,$year,$bio];
  if ($picName) $params[] = $picName;
  $params[] = $uid;
  db()->prepare($sql)->execute($params);
  $msg='Profile updated.';
}

$st = db()->prepare("SELECT email, full_name, degree, college, academic_year, bio, profile_picture FROM students WHERE student_id=?");
$st->execute([$uid]);
$user=$st->fetch();
?>
<!doctype html><html><head><meta charset="utf-8"><title>Edit Profile</title>
<link rel="stylesheet" href="/COMP2030-ASSIGNMENT/src/css/style.css?v=8"></head><body>
<h1>Edit Profile</h1>
<?php if($msg) echo "<p style='color:green'>".htmlspecialchars($msg)."</p>"; ?>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
  <p>Email: <strong><?=htmlspecialchars($user['email'])?></strong></p>
  <label>Full Name <input name="full_name" value="<?=htmlspecialchars($user['full_name'])?>" required></label><br>
  <label>Degree <input name="degree" value="<?=htmlspecialchars($user['degree'])?>"></label><br>
  <label>College <input name="college" value="<?=htmlspecialchars($user['college'])?>"></label><br>
  <label>Academic Year <input type="number" name="academic_year" min="1" max="12" value="<?= (int)$user['academic_year']?>"></label><br>
  <label>Bio<br><textarea name="bio" rows="5" cols="50"><?=htmlspecialchars($user['bio'])?></textarea></label><br>
  <label>Profile Picture <input type="file" name="profile_picture" accept="image/*"></label><br>
  <button>Save</button>
</form>
<p><a href="/COMP2030-Assignment/src/StudentPage/Public/index.php">Back</a></p>
</body></html>
