<?php
require_once dirname(__DIR__).'/inc/init.inc.php';

$uid = (int)($_GET['u'] ?? current_user_id() ?? 0);

$q = db()->prepare(
  'SELECT st.*, COALESCE(r.avg_rating,0) avg_rating, COALESCE(r.rating_count,0) rating_count
     FROM students st
     LEFT JOIN student_ratings r ON r.student_id = st.student_id
    WHERE st.student_id = ?'
);
$q->execute([$uid]);
$student = $q->fetch();
if(!$student){ http_response_code(404); echo 'Profile not found'; exit; }

// (1) Are we viewing our own profile? If so, show the Edit button.
$self = (current_user_id() === (int)$student['student_id']);

$s = db()->prepare(
  'SELECT ss.id offer_id, s.name, s.category
     FROM student_skills ss
     JOIN skills s ON s.skill_id=ss.skill_id
    WHERE ss.student_id=? AND ss.role="offered"
    ORDER BY ss.id DESC'
);
$s->execute([$uid]);
$offers = $s->fetchAll();
?>

<?php include dirname(__DIR__).'/templates/header.php'; ?>

  <?php if ($self): ?>
  <p style="text-align:right">
    <a class="btn" href="/transactions.php">View Transactions</a>
    <a class="btn" href="/profile_edit.php">Edit Profile</a>
  </p>
<?php endif; ?>


<h1><?= h($student['full_name']) ?></h1>

<?php // (4) Show avatar if set ?>
<?php if (!empty($student['profile_picture'])): ?>
  <p>
    <img
      src="<?= h($student['profile_picture']) ?>"
      alt="Avatar of <?= h($student['full_name']) ?>"
      style="max-width:120px;border-radius:8px"
    >
  </p>
<?php endif; ?>

<p><?= h($student['degree']) ?> • <?= h($student['college']) ?> • Year <?= h((string)$student['academic_year']) ?></p>
<p><?= nl2br(h($student['bio'])) ?></p>

<p>
  Rating:
  <?= str_repeat('★', (int)round($student['avg_rating'])) .
     str_repeat('☆', 5 - (int)round($student['avg_rating'])) ?>
  (<?= (int)$student['rating_count'] ?>)
</p>

<h2>Skills Offered</h2>
<ul class="list">
  <?php foreach($offers as $sk): ?>
    <li>
      <a href="/skill.php?id=<?= (int)$sk['offer_id'] ?>"><?= h($sk['name']) ?></a>
      — <?= h($sk['category']) ?>
    </li>
  <?php endforeach; ?>
  <?php if (empty($offers)): ?>
    <li class="muted">No offered skills yet.</li>
  <?php endif; ?>
</ul>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
