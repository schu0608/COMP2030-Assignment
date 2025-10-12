<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = current_user_id();
$bal = $uid ? (float)db()->query('SELECT fuss_credits FROM students WHERE student_id='.$uid)->fetchColumn() : 0.0;
json_out(['balance'=>$bal]);