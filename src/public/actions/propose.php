<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$tid = (int)($_POST['transaction_id'] ?? 0);
$hours = isset($_POST['hours']) ? (float)$_POST['hours'] : null;
$t = db()->prepare('SELECT requester_id, provider_id, status FROM transactions WHERE transaction_id=?'); $t->execute([$tid]); $tx = $t->fetch();
if(!$tx) redirect('/messages.php?e=nf');
if($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']){ http_response_code(403); exit('Forbidden'); }


db()->prepare('UPDATE transactions SET status="proposed", proposed_hours=? WHERE transaction_id=?')->execute([$hours,$tid]);
$body = 'Proposed hours: '.($hours ?: '-');
db()->prepare('INSERT INTO messages (transaction_id, sender_id, body, type) VALUES (?,?,?,"proposal")')->execute([$tid,$uid,$body]);
redirect('/thread.php?id='.$tid);