<?php require_once dirname(__DIR__,2).'/inc/init.inc.php'; ?>
<?php include dirname(__DIR__,2).'/templates/header.php'; ?>
<h1>Log in</h1>
<form method="post" action="/actions/auth_login.php">
<?= csrf_field() ?>
<label>Email <input type="email" name="email" required></label>
<label>Password <input type="password" name="password" required></label>
<button class="btn">Log in</button>
</form>
<?php include dirname(__DIR__,2).'/templates/footer.php'; ?>