<?php
// footer.php (closes out the page that header.php opened)

$uid = function_exists('current_user_id') ? (int) current_user_id() : 0;
$showAdmin = false;

if ($uid) {
  if (function_exists('is_admin')) {
    $showAdmin = is_admin($uid);
  } else {
    // Fallback: treat first account as admin
    $showAdmin = ($uid === 1);
  }
}
?>

<footer class="site-footer container">
  <p>
    &copy; <?= date('Y') ?> FUSS
    <?php if ($showAdmin): ?>
      &middot; <a href="/admin/dashboard.php" class="muted">Admin</a>
      <!-- If you want quick sub-links, uncomment:
      &nbsp;· <a href="/admin/students.php" class="muted">Students</a>
      &nbsp;· <a href="/admin/skills.php" class="muted">Skills</a>
      &nbsp;· <a href="/admin/credits.php" class="muted">Credits</a>
      &nbsp;· <a href="/admin/moderation.php" class="muted">Moderation</a>
      -->
    <?php endif; ?>
  </p>
</footer>

<!-- App scripts -->
<script src="/assets/app.js" defer></script>

</main>
</body>
</html>
