<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$tid = (int)($_POST['transaction_id'] ?? 0); $body = trim($_POST['body'] ?? '');
if($body==='') redirect('/thread.php?id='.$tid.'&e=msg');
$own = db()->prepare('SELECT requester_id, provider_id FROM transactions WHERE transaction_id=?'); $own->execute([$tid]); $tx = $own->fetch();
if(!$tx) redirect('/messages.php?e=nf');
if($uid !== (int)$tx['requester_id'] && $uid !== (int)$tx['provider_id']){ http_response_code(403); exit('Forbidden'); }


db()->prepare('INSERT INTO messages (transaction_id, sender_id, body) VALUES (?,?,?)')->execute([$tid,$uid,$body]);
redirect('/thread.php?id='.$tid);