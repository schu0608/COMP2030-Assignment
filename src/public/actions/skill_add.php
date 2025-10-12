<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$role = $_POST['role'] === 'requested' ? 'requested' : 'offered';
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
if ($name==='') redirect('/profile_edit.php?e=skill');


// Find or create skill in catalog
$sk = db()->prepare('SELECT skill_id FROM skills WHERE name=?');
$sk->execute([$name]);
$skill = $sk->fetch();
if(!$skill){
$ins = db()->prepare('INSERT INTO skills (name, category, description) VALUES (?,?,?)');
$ins->execute([$name,$category,$description]);
$skill_id = (int)db()->lastInsertId();
} else { $skill_id = (int)$skill['skill_id']; }


// Link to student
$link = db()->prepare('INSERT INTO student_skills (student_id, skill_id, role, details) VALUES (?,?,?,?)');
$link->execute([$uid,$skill_id,$role,$description]);
redirect('/profile_edit.php');