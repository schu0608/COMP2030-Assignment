<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
validate_csrf();
$full = trim($_POST['full_name'] ?? '');
if ($full === '') { $full = trim(($_POST['first_name'] ?? '').' '.($_POST['last_name'] ?? '')); }
$email = strtolower(trim($_POST['email'] ?? ''));
$pass = $_POST['password'] ?? '';
if(!$full || !preg_match('/^[^@\s]+@flinders\.edu\.au$/', $email) || strlen($pass)<8){ redirect('/auth/register.php?e=bad'); }
$hash = password_hash($pass, PASSWORD_DEFAULT);
try{
$st = db()->prepare('INSERT INTO students (email, password, full_name, academic_year, fuss_credits, active) VALUES (?,?,?,?,0,1)');
$st->execute([$email,$hash,$full,0]);
$_SESSION['user_id'] = (int)db()->lastInsertId();
redirect('/browse.php');
}catch(PDOException $e){ redirect('/auth/register.php?e=exists'); }