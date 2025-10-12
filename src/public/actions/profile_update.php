<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$full = trim($_POST['full_name'] ?? '');
$degree = trim($_POST['degree'] ?? '');
$college = trim($_POST['college'] ?? '');
$year = (int)($_POST['academic_year'] ?? 0);
$bio = trim($_POST['bio'] ?? '');
$avatarPath = null;


// Optional avatar upload (to /uploads)
if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
$f = $_FILES['avatar'];
if ($f['error'] === UPLOAD_ERR_OK) {
$mime = mime_content_type($f['tmp_name']);
if (preg_match('#^image/(png|jpe?g|gif|webp)$#i', $mime)) {
$ext = pathinfo($f['name'], PATHINFO_EXTENSION);
$safe = 'u'.$uid.'_'.bin2hex(random_bytes(4)).'.'.strtolower($ext);
$destDir = dirname(__DIR__,2).'/public/uploads';
if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
$dest = $destDir.'/'.$safe;
if (move_uploaded_file($f['tmp_name'], $dest)) {
$avatarPath = '/uploads/'.$safe;
}
}
}
}


$sql = 'UPDATE students SET full_name=?, degree=?, college=?, academic_year=?, bio=?'.($avatarPath? ', profile_picture=?':'').' WHERE student_id=?';
$params = [$full,$degree,$college,$year,$bio];
if ($avatarPath) $params[] = $avatarPath;
$params[] = $uid;


$st = db()->prepare($sql); $st->execute($params);
redirect('/profile.php');