<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$dbName = db()->query('SELECT DATABASE()')->fetchColumn();
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$sample = db()->query('SELECT student_id,email,LEFT(password,7) pw7,fuss_credits FROM students ORDER BY student_id DESC LIMIT 3')->fetchAll();
json_out(['database'=>$dbName,'tables'=>$tables,'students'=>$sample]);