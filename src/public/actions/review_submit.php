<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$tid = (int)($_POST['transaction_id'] ?? 0); $stars = (int)($_POST['stars'] ?? 0); $comment = trim($_POST['review'] ?? '');
if($stars<1||$stars>5) redirect('/thread.php?id='.$tid.'&e=stars');
$t = db()->prepare('SELECT requester_id, provider_id, status FROM transactions WHERE transaction_id=?'); $t->execute([$tid]); $tx = $t->fetch();
if(!$tx || $tx['status']!=='confirmed') redirect('/thread.php?id='.$tid.'&e=state');
$reviewee = ($uid===(int)$tx['requester_id']) ? (int)$tx['provider_id'] : (int)$tx['requester_id'];
db()->prepare('INSERT INTO reviews (transaction_id, reviewer_id, reviewee_id, stars, comment) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE stars=VALUES(stars), comment=VALUES(comment)')->execute([$tid,$uid,$reviewee,$stars,$comment]);
redirect('/thread.php?id='.$tid.'&ok=review');