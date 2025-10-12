<?php
require_once dirname(__DIR__,2).'/inc/init.inc.php';
$uid = require_login(); validate_csrf();
$tid = (int)($_POST['transaction_id'] ?? 0); $final_hours = (float)($_POST['hours'] ?? 0);
$pdo = db(); $pdo->beginTransaction();
$st = $pdo->prepare('SELECT * FROM transactions WHERE transaction_id=? FOR UPDATE'); $st->execute([$tid]); $tx = $st->fetch();
if(!$tx){ $pdo->rollBack(); redirect('/messages.php?e=nf'); }
$rid = (int)$tx['requester_id']; $pid = (int)$tx['provider_id'];
$hours = $final_hours > 0 ? $final_hours : ((float)$tx['proposed_hours'] ?: (float)$tx['hours']);
$pdo->prepare('UPDATE transactions SET hours=?, fuss_credit_amount=? WHERE transaction_id=?')->execute([$hours,$hours,$tid]);


if($uid === $rid){
if(in_array($tx['status'], ['accepted','proposed'])){
$pdo->prepare('UPDATE transactions SET status="confirm_requester" WHERE transaction_id=?')->execute([$tid]);
} elseif($tx['status']==='confirm_provider'){
$bal = (float)$pdo->query('SELECT fuss_credits FROM students WHERE student_id='.$rid.' FOR UPDATE')->fetchColumn();
if($bal < $hours){ $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=credits'); }
$pdo->exec('UPDATE students SET fuss_credits = fuss_credits - '.$hours.' WHERE student_id='.$rid);
$pdo->exec('UPDATE students SET fuss_credits = fuss_credits + '.$hours.' WHERE student_id='.$pid);
$pdo->prepare('UPDATE transactions SET status="confirmed" WHERE transaction_id=?')->execute([$tid]);
}
} elseif($uid === $pid){
if(in_array($tx['status'], ['accepted','proposed'])){
$pdo->prepare('UPDATE transactions SET status="confirm_provider" WHERE transaction_id=?')->execute([$tid]);
} elseif($tx['status']==='confirm_requester'){
$bal = (float)$pdo->query('SELECT fuss_credits FROM students WHERE student_id='.$rid.' FOR UPDATE')->fetchColumn();
if($bal < $hours){ $pdo->rollBack(); redirect('/thread.php?id='.$tid.'&e=credits'); }
$pdo->exec('UPDATE students SET fuss_credits = fuss_credits - '.$hours.' WHERE student_id='.$rid);
$pdo->exec('UPDATE students SET fuss_credits = fuss_credits + '.$hours.' WHERE student_id='.$pid);
$pdo->prepare('UPDATE transactions SET status="confirmed" WHERE transaction_id=?')->execute([$tid]);
}
}
$pdo->commit();
redirect('/thread.php?id='.$tid);