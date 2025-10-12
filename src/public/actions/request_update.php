<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$tid = (int)($_POST['transaction_id'] ?? 0); $action = $_POST['action'] ?? '';
$t = db()->prepare('SELECT * FROM transactions WHERE transaction_id=?'); $t->execute([$tid]); $tx = $t->fetch();
if(!$tx) redirect('/messages.php?e=nf');
$isProvider = ($uid === (int)$tx['provider_id']);
if($action==='accept' && $isProvider && $tx['status']==='pending'){
db()->prepare('UPDATE transactions SET status="accepted" WHERE transaction_id=?')->execute([$tid]);
db()->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,"system")')
->execute([$tid,$uid,'Provider accepted.']);
}
if($action==='reject' && $isProvider && $tx['status']==='pending'){
db()->prepare('UPDATE transactions SET status="rejected" WHERE transaction_id=?')->execute([$tid]);
db()->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,"system")')
->execute([$tid,$uid,'Provider rejected.']);
}
redirect('/thread.php?id='.$tid);