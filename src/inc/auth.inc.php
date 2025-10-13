<?php
// src/inc/auth.inc.php
declare(strict_types=1);

$ROOT = dirname(__DIR__); // points to /src

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/**
 * Require a logged-in user. If not logged in, render a nice page and exit.
 * @return int user id
 */
function require_login(): int {
  if (empty($_SESSION['user_id'])) {
    http_response_code(401);

    // For “return after login”
    $next = $_SERVER['REQUEST_URI'] ?? '/';
    // Make the variable available to the template
    $GLOBALS['_auth_required_next'] = $next;

    // Render a friendly page
    require dirname(__DIR__) . '/templates/auth_required.php';
    exit;
  }
  return (int)$_SESSION['user_id'];
}

/**
 * Optional: admin guard (keeps your old behaviour but with nice page if not logged in).
 */
function require_admin(): int {
  $uid = require_login();
  // TEMP: either user_id=1 or a session flag can be admin
  if ($uid !== 1 && empty($_SESSION['is_admin'])) {
    http_response_code(403);
    // Reuse the same template but with a different message if you want,
    // or keep this minimal:
    echo 'Forbidden: admin only.';
    exit;
  }
  return $uid;
}
