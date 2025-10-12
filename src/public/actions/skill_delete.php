<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$id = (int)($_POST['id'] ?? 0);
$own = db()->prepare('SELECT student_id FROM student_skills WHERE id=?');
$own->execute([$id]);
$row = $own->fetch();
if(!$row || (int)$row['student_id'] !== $uid){ http_response_code(403); exit('Forbidden'); }


db()->prepare('DELETE FROM student_skills WHERE id=?')->execute([$id]);
redirect('/profile_edit.php');