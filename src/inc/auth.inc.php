<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

const AUTH_LOGIN    = '/auth/login.php';
const AUTH_REGISTER = '/auth/register.php';


function current_user_id(): ?int {
  return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_login(): int {
  $uid = current_user_id();
  if ($uid) return $uid;

  $next = $_SERVER['REQUEST_URI'] ?? '/';
  header('Location: ' . AUTH_LOGIN . '?next=' . urlencode($next));
  exit;
}

function require_admin(): int {
  $uid = require_login();
  // TEMP: treat user_id=1 or session flag as admin
  if ($uid !== 1 && empty($_SESSION['is_admin'])) {
    http_response_code(403);
    exit('Forbidden: admin only.');
  }
  return $uid;
}
