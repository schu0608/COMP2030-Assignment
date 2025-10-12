<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$err=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_validate();
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';
  if (login_user($email,$pass)) { header('Location: /index.php'); exit; }
  else $err='Invalid email or password';
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Login</title></head><body>
<h1>Login</h1>
<?php if($err) echo "<p style='color:red'>".htmlspecialchars($err)."</p>"; ?>
<form method="post">
  <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
  <label>Email <input name="email" type="email" required></label><br>
  <label>Password <input name="password" type="password" required></label><br>
  <button>Login</button>
</form>
<p>No account? <a href="/register.php">Register</a></p>
</body></html>
