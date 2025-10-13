<?php

if (PHP_SAPI !== 'cli') {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}


if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = __DIR__ . '/../.sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_secure', '0'); 
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}


require_once __DIR__ . '/dbconn.inc.php';
require_once __DIR__ . '/helpers.inc.php';
require_once __DIR__ . '/auth.inc.php';
require_once __DIR__ . '/csrf.inc.php';
require_once __DIR__ . '/auth.inc.php';

