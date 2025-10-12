<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
validate_csrf();
$email = strtolower(trim($_POST['email'] ?? ''));
$pass = $_POST['password'] ?? '';
$st = db()->prepare('SELECT student_id, password FROM students WHERE email = ?');
$st->execute([$email]); $u = $st->fetch();
if($u && password_verify($pass, $u['password'])){ $_SESSION['user_id'] = (int)$u['student_id']; redirect('/browse.php'); }
redirect('/auth/login.php?e=invalid');