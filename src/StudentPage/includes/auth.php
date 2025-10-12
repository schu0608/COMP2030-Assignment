<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function session_boot(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
      'cookie_httponly'=>true, 'cookie_samesite'=>'Lax', 'use_strict_mode'=>true,
    ]);
  }
}

function current_user_id(): ?int { session_boot(); return $_SESSION['uid'] ?? null; }

function require_login(): void {
  if (!current_user_id()) { header('Location: /login.php'); exit; }
}

function login_user(string $email, string $password): bool {
  $st = db()->prepare("SELECT student_id, password FROM students WHERE email=?");
  $st->execute([$email]);
  $row = $st->fetch();
  if ($row && password_verify($password, $row['password'])) {
    session_boot();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['student_id'];
    return true;
  }
  return false;
}

function logout_user(): void {
  session_boot(); $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
  }
  session_destroy();
}
