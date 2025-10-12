<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';

$ok=false; $errors=[];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_validate();
  $email  = strtolower(trim($_POST['email'] ?? ''));
  $pass   = $_POST['password'] ?? '';
  $name   = sanitize($_POST['full_name'] ?? '', 100);
  $degree = sanitize($_POST['degree'] ?? '', 100);
  $college= sanitize($_POST['college'] ?? '', 100);
  $year   = (int)($_POST['academic_year'] ?? 1);

  if (!is_valid_flinders_email($email)) $errors[]='Use your @flinders.edu.au email';
  if (strlen($pass) < 8) $errors[]='Password must be at least 8 characters';
  if ($name === '') $errors[]='Full name is required';

  if (!$errors) {
    $exists = db()->prepare("SELECT 1 FROM students WHERE email=?");
    $exists->execute([$email]);
    if ($exists->fetch()) {
      $errors[]='Email already registered';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $ins = db()->prepare("
        INSERT INTO students (email, password, full_name, degree, college, academic_year, bio, profile_picture, fuss_credits)
        VALUES (?,?,?,?,?,?, '', NULL, 0)
      ");
      $ins->execute([$email,$hash,$name,$degree,$college,$year]);
      $ok=true;
    }
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Register</title></head><body>
<h1>Register</h1>
<?php if ($ok): ?>
  <p>Success. <a href="/login.php">Login</a></p>
<?php else: ?>
  <?php foreach ($errors as $e) echo "<p style='color:red'>".htmlspecialchars($e)."</p>"; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
    <label>Flinders Email <input type="email" name="email" required></label><br>
    <label>Password <input type="password" name="password" minlength="8" required></label><br>
    <label>Full Name <input name="full_name" required></label><br>
    <label>Degree <input name="degree"></label><br>
    <label>College <input name="college"></label><br>
    <label>Academic Year <input type="number" name="academic_year" min="1" max="8" value="1"></label><br>
    <button>Register</button>
  </form>
  <p>Have an account? <a href="/login.php">Login</a></p>
<?php endif; ?>
</body></html>
