<?php
// Ensure core is loaded once
if (!function_exists('current_user_id')) {
  require_once dirname(__DIR__).'/inc/init.inc.php';
}

// Active tab helper
function nav_active(string $file): string {
  $path = $_SERVER['SCRIPT_NAME'] ?? '';
  return (substr($path, -strlen($file)) === $file) ? 'active' : '';
}

// Page title (set on each page before including header.php if you want)
$pageTitle = $pageTitle ?? '';

// Current user name for header
$authName = null;
if ($uid = (int)current_user_id()) {
  try {
    $st = db()->prepare('SELECT full_name FROM students WHERE student_id=?');
    $st->execute([$uid]);
    $authName = $st->fetchColumn() ?: null;
  } catch (Throwable $e) { /* ignore */ }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php if (function_exists('csrf_token')): ?>
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="/assets/style.css">
  <link rel="icon" href="/assets/flinders-mark.svg" type="image/svg+xml">
  <link rel="alternate icon" href="/assets/cat/flinders-mark.png" sizes="32x32">
  <title><?= $pageTitle ? h($pageTitle).' — ' : '' ?>FUSS</title>
</head>
<body>

<header class="masthead">
  <div class="masthead-top container">
    <a class="brand" href="/index.php">
      <img
        class="brand-mark"
        src="/assets/flinders-mark.svg"
        onerror="this.onerror=null;this.src='/assets/cat/flinders-mark.png';"
        alt="Flinders crest"
        width="44" height="44" decoding="async" fetchpriority="high">
      <span class="brand-text">
        <strong>Flinders Uni Skill Share</strong>
        <small>Exchange skills. Earn FUSSCredits. Build community.</small>
      </span>
    </a>

    <div class="masthead-right">
      <?php if ($authName): ?>
        <div class="user-stack">
          <div class="username"><?= h($authName) ?></div>
          <div class="user-row">
            <span class="credit-pill">Credits: <span id="nav-credit-balance">—</span></span>
            <form method="post" action="/actions/auth_logout.php" class="logout-form">
              <?= function_exists('csrf_field') ? csrf_field() : '' ?>
              <button class="btn btn--ghost">Logout</button>
            </form>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn--ghost" href="/auth/login.php">Log in</a>
        <a class="btn" href="/auth/register.php">Sign up</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="masthead-nav container">
    <nav class="tabs">
      <a class="<?= nav_active('/index.php') ?>"    href="/index.php">Home</a>
      <a class="<?= nav_active('/browse.php') ?>"   href="/browse.php">Browse Skills</a>
      <a class="<?= nav_active('/messages.php') ?>" href="/messages.php">Requests</a>
      <a class="<?= nav_active('/profile.php') ?>"  href="/profile.php">Profile</a>
    </nav>
    <div class="page-title"><?= h($pageTitle) ?></div>
  </div>
</header>

<main class="container">
