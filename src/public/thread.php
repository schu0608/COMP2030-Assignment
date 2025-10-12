<?php require_once dirname(__DIR__).'/inc/init.inc.php'; $uid = require_login();
$id = (int)($_GET['id'] ?? 0);
$sql = 'SELECT t.*, s.name AS skill_name, req.full_name AS req_name, prov.full_name AS prov_name
FROM transactions t
JOIN skills s ON s.skill_id = t.skill_id
JOIN students req ON req.student_id = t.requester_id
JOIN students prov ON prov.student_id = t.provider_id
WHERE t.transaction_id = ?';
$stmt = db()->prepare($sql); $stmt->execute([$id]); $t = $stmt->fetch();
if(!$t){ http_response_code(404); echo 'Not found'; exit; }
if($uid !== (int)$t['requester_id'] && $uid !== (int)$t['provider_id']){ http_response_code(403); exit('Forbidden'); }


$m = db()->prepare('SELECT m.*, st.full_name sender FROM messages m JOIN students st ON st.student_id=m.sender_id WHERE transaction_id=? ORDER BY created_at');
$m->execute([$id]); $msgs = $m->fetchAll();
?>
<?php include dirname(__DIR__).'/templates/header.php'; ?>
<h1><?= h($t['skill_name']) ?></h1>
<p>Status: <strong><?= h($t['status']) ?></strong> • Hours: <?= h((string)$t['hours']) ?> • Credits: <?= h((string)$t['fuss_credit_amount']) ?></p>


<h2>Conversation</h2>
<ul class="messages">
<?php foreach($msgs as $msg): ?>
<li><strong><?= h($msg['sender']) ?>:</strong> <?= nl2br(h($msg['body'])) ?> <small>(<?= h($msg['type']) ?>)</small></li>
<?php endforeach; ?>
</ul>


<form method="post" action="/actions/message_send.php">
<?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$id ?>">
<input name="body" placeholder="Write a message…" required>
<button class="btn">Send</button>
</form>


<?php if ($uid === (int)$t['provider_id'] && $t['status']==='pending'): ?>
<form method="post" action="/actions/request_update.php" class="inline">
<?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$id ?>">
<button class="btn" name="action" value="accept">Accept</button>
<button class="btn btn--ghost" name="action" value="reject">Reject</button>
</form>
<?php endif; ?>


<h2>Propose alternative</h2>
<form method="post" action="/actions/propose.php">
<?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$id ?>">
<label>Hours <input type="number" name="hours" step="0.5" min="0.5" max="10"></label>
<button class="btn">Propose</button>
</form>


<?php if (in_array($t['status'], ['accepted','proposed','confirm_requester','confirm_provider'])): ?>
<h2>Confirm completion</h2>
<form method="post" action="/actions/service_confirm.php">
<?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$id ?>">
<label>Final hours <input type="number" name="hours" step="0.5" min="0.5" max="10" value="<?= h((string)$t['hours']) ?>" required></label>
<button class="btn">Confirm</button>
</form>
<?php endif; ?>


<?php if ($t['status']==='confirmed'): ?>
<h2>Leave a review</h2>
<form method="post" action="/actions/review_submit.php">
<?= csrf_field() ?><input type="hidden" name="transaction_id" value="<?= (int)$id ?>">
<select name="stars" required>
<option value="5">★★★★★</option><option value="4">★★★★☆</option><option value="3">★★★☆☆</option><option value="2">★★☆☆☆</option><option value="1">★☆☆☆☆</option>
</select>
<textarea name="review" rows="3" maxlength="300" placeholder="How was it?"></textarea>
<button class="btn">Submit review</button>
</form>
<?php endif; ?>
<?php include dirname(__DIR__).'/templates/footer.php'; ?>