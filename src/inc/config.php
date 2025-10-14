<?php
declare(strict_types=1);

$CONFIG = [
  'db_dsn'  => 'mysql:host=db;dbname=fussdb;charset=utf8mb4',
  'db_user' => 'root',
  'db_pass' => 'password',
  'email_domain' => 'flinders.edu.au',
  'uploads_dir' => __DIR__ . '/../public/uploads',
  'uploads_url' => '/uploads',
  'max_upload_bytes' => 2 * 1024 * 1024,
];
