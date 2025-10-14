<?php
$ROOT = dirname(__DIR__);

$pageTitle = 'Sign in required';
$next = $GLOBALS['_auth_required_next'] ?? '/';

include $ROOT . '/templates/header.php';
?>
<section class="container" style="max-width: 780px; margin-top: 32px;">
  <article class="card" style="padding: 24px;">
    <h1 style="margin-top:0">Sign in required</h1>
    <p class="muted" style="margin: 6px 0 18px;">
      You need to be logged in to view this page.
    </p>

    <div class="grid grid--2">
      <div>
        <a class="btn btn--primary" href="/login.php?next=<?= urlencode($next) ?>">Log in</a>
        <a class="btn" href="/register.php?next=<?= urlencode($next) ?>">Create an account</a>
      </div>
      <div style="text-align:right">
        <a class="btn btn--ghost" href="/">← Back to Home</a>
      </div>
    </div>

    <hr style="margin: 18px 0; border: 0; border-top: 1px solid var(--border)">

    <p class="muted" style="margin:0">
      Tip: after signing in, we’ll bring you right back here.
    </p>
  </article>
</section>
<?php include $ROOT . '/templates/footer.php'; ?>
