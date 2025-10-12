<?php
declare(strict_types=1);

$CONFIG = [
  'db_dsn'  => 'mysql:host=localhost;dbname=fussdb;charset=utf8mb4',
  'db_user' => 'root',
  'db_pass' => '',
  'email_domain' => 'flinders.edu.au',
  'uploads_dir' => __DIR__ . '/../public/uploads',
  'uploads_url' => '/uploads',
  'max_upload_bytes' => 2 * 1024 * 1024, // 2MB
];
