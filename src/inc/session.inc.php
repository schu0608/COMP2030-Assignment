<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    
    $sessionPath = realpath(__DIR__ . '/..') . '/.sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }

    ini_set('session.save_path', $sessionPath);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0'); 
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}
