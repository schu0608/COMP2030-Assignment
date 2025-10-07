<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function sanitize(string $s, int $max=5000): string {
  $s = trim($s);
  return mb_substr($s, 0, $max);
}

function is_valid_flinders_email(string $email): bool {
  global $CONFIG;
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
  $domain = substr(strrchr($email, "@"), 1);
  return strtolower($domain) === strtolower($CONFIG['email_domain']);
}

function upload_image(?array $file): ?string {
  global $CONFIG;
  if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if ($file['error'] !== UPLOAD_ERR_OK) return null;
  if ($file['size'] > $CONFIG['max_upload_bytes']) return null;

  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
  $ext = match($mime){ 'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp', default=>null };
  if (!$ext) return null;

  if (!is_dir($CONFIG['uploads_dir'])) mkdir($CONFIG['uploads_dir'], 0775, true);
  $name = bin2hex(random_bytes(16)).".$ext";
  $dest = $CONFIG['uploads_dir'] . "/$name";
  if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
  return $name;
}
