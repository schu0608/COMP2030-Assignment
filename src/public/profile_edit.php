<?php
require_once dirname(__DIR__).'/inc/init.inc.php';
$uid = require_login();
// Only allow editing own profile
$q = db()->prepare('SELECT * FROM students WHERE student_id=?');
$q->execute([$uid]);
$me = $q->fetch();
if(!$me){ http_response_code(404); echo 'Profile not found'; exit; }


// Fetch offered & requested skills
$off = db()->prepare('SELECT ss.id, s.name, s.category FROM student_skills ss JOIN skills s ON s.skill_id=ss.skill_id WHERE ss.student_id=? AND ss.role="offered" ORDER BY ss.id DESC');
$off->execute([$uid]);
$offered = $off->fetchAll();


$req = db()->prepare('SELECT ss.id, s.name, s.category FROM student_skills ss JOIN skills s ON s.skill_id=ss.skill_id WHERE ss.student_id=? AND ss.role="requested" ORDER BY ss.id DESC');
$req->execute([$uid]);
$requested = $req->fetchAll();
?>
<?php include dirname(__DIR__).'/templates/header.php'; ?>
<h1>Edit profile</h1>
<form method="post" action="/actions/profile_update.php" enctype="multipart/form-data" class="stack">
<?= csrf_field() ?>
<label>Full name <input name="full_name" value="<?= h($me['full_name']) ?>" required></label>
<label>Email <input value="<?= h($me['email']) ?>" disabled></label>
<div class="grid grid--2">
<label>Degree <input name="degree" value="<?= h($me['degree']) ?>"></label>
<label>College <input name="college" value="<?= h($me['college']) ?>"></label>
</div>
<label>Academic Year
<select name="academic_year">
<option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
</select>
<label>Bio <textarea name="bio" rows="4"><?= h($me['bio']) ?></textarea></label>
<label>Profile picture <input type="file" name="avatar" accept="image/*"></label>
<button class="btn">Save changes</button>
</form>

<hr>
<h2>Offered skills</h2>
<ul class="list">
<?php foreach($offered as $s): ?>
<li>
<?= h($s['name']) ?> — <?= h($s['category']) ?>
<form method="post" action="/actions/skill_delete.php" style="display:inline">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
<button class="btn btn--ghost">Remove</button>
</form>
</li>
<?php endforeach; ?>
</ul>
<form method="post" action="/actions/skill_add.php" class="stack">
<?= csrf_field() ?>
<input type="hidden" name="role" value="offered">
<label>Skill name <input name="name" placeholder="e.g., COMP1002 Tutoring" required></label>
<label>Category
<select name="category">
<option>Academic Help</option><option>Tech Support</option><option>Life Skills</option><option>Practical</option>
</select>
</label>
<label>Description <textarea name="description" rows="3" placeholder="What you offer"></textarea></label>
<button class="btn">Add offered skill</button>
</form>


<hr>
<h2>Requested skills</h2>
<ul class="list">
<?php foreach($requested as $s): ?>
<li>
<?= h($s['name']) ?> — <?= h($s['category']) ?>
<form method="post" action="/actions/skill_delete.php" style="display:inline">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
<button class="btn btn--ghost">Remove</button>
</form>
</li>
<?php endforeach; ?>
</ul>
<form method="post" action="/actions/skill_add.php" class="stack">
<?= csrf_field() ?>
<input type="hidden" name="role" value="requested">
<label>Skill name <input name="name" placeholder="e.g., Resume Review" required></label>
<label>Category
<select name="category">
<option>Academic Help</option><option>Tech Support</option><option>Life Skills</option><option>Practical</option>
</select>
</label>
<label>Description <textarea name="description" rows="3" placeholder="What help you need"></textarea></label>
<button class="btn">Add requested skill</button>
</form>
<?php include dirname(__DIR__).'/templates/footer.php'; ?>