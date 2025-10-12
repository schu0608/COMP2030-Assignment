<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null; global $CONFIG;
  if ($pdo === null) {
    $pdo = new PDO(
      $CONFIG['db_dsn'], $CONFIG['db_user'], $CONFIG['db_pass'],
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
  }
  return $pdo;
}
