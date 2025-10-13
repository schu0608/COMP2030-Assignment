<?php
// src/public/auth/login.php
declare(strict_types=1);

$ROOT = dirname(__DIR__, 2); // points to /src
require_once $ROOT . '/inc/init.inc.php';   // must set up db(), start session, etc.
require_once $ROOT . '/inc/auth.inc.php';   // AUTH_LOGIN/AUTH_REGISTER + helpers

$pdo  = db();
$next = $_GET['next'] ?? '/';                 // where to go after login
$error = null;

// If already logged in, bounce to next
if (current_user_id()) {
  header('Location: ' . $next);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Optional: if you have CSRF, call validate_csrf();

  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $error = 'Please enter your email and password.';
  } else {
    try {
      $st = $pdo->prepare('SELECT student_id, password, full_name FROM students WHERE email = ? AND active = 1');
      $st->execute([$email]);
      $row = $st->fetch(PDO::FETCH_ASSOC);

      if (!$row || !password_verify($pass, $row['password'])) {
        // Wrong credentials
        $error = 'Invalid email or password.';
      } else {
        // Success: log in
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['user_id']   = (int)$row['student_id'];
        $_SESSION['full_name'] = (string)$row['full_name'];

        // Optional: set admin flag here if you have a roles table
        // $_SESSION['is_admin'] = (bool)$isAdmin;

        header('Location: ' . $next);
        exit;
      }
    } catch (Throwable $e) {
      // Don’t leak details to user; log it and show generic error
      error_log('Login failed: ' . $e->getMessage());
      $error = 'Something went wrong while logging you in. Please try again.';
    }
  }
}

// Page title for header
$pageTitle = 'Log in';
include $ROOT . '/templates/header.php';
?>
<h1>Log in</h1>

<?php if ($error): ?>
  <div class="notice error" role="alert" style="margin-bottom:12px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="grid" style="max-width:420px">
  <?php if (function_exists('csrf_field')) echo csrf_field(); ?>
  <input type="hidden" name="next" value="<?= htmlspecialchars($next) ?>">

  <label class="label">Email</label>
  <input type="email" name="email" placeholder="you@flinders.edu.au"
         value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

  <label class="label">Password</label>
  <input type="password" name="password" placeholder="••••••••" required>

  <button class="btn btn--primary" type="submit" style="margin-top:8px">Log in</button>

  <p class="muted" style="margin-top:12px">
    Don’t have an account?
    <a href="<?= AUTH_REGISTER ?>?next=<?= urlencode($next) ?>">Create one</a>.
  </p>
</form>

<?php include $ROOT . '/templates/footer.php'; ?>
