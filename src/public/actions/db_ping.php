<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
try { $one = db()->query('SELECT 1')->fetchColumn(); json_out(['ok'=>true,'select1'=>(int)$one]); }
catch (Throwable $e) { json_out(['ok'=>false,'error'=>$e->getMessage()],500); }