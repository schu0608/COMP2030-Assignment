<?php require_once dirname(__DIR__,2).'/inc/init.inc.php'; ?>
<?php include dirname(__DIR__,2).'/templates/header.php'; ?>
<h1>Create account</h1>
<form id="reg" method="post" action="/actions/auth_register.php" novalidate>
<?= csrf_field() ?>
<label>First name <input id="first" name="first_name" required></label>
<label>Last name <input id="last" name="last_name" required></label>
<label>Email <input id="email" type="email" name="email" required placeholder="abc1234@flinders.edu.au" pattern="^[^@\s]+@flinders\.edu\.au$"></label>
<label>Password <input type="password" name="password" minlength="8" required></label>
<input type="hidden" name="full_name" id="full_name">
<button class="btn">Sign up</button>
</form>
<script>
const f = document.getElementById('reg');
f.addEventListener('submit', ()=>{ document.getElementById('full_name').value = `${document.getElementById('first').value} ${document.getElementById('last').value}`.trim(); });
const email = document.getElementById('email');
email.addEventListener('input', ()=>email.setCustomValidity(''));
email.addEventListener('invalid', ()=>{ if(email.validity.patternMismatch){ email.setCustomValidity('Use your @flinders.edu.au email'); }});
</script>
<?php include dirname(__DIR__,2).'/templates/footer.php'; ?>