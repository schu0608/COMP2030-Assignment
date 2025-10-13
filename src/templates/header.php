<?php
// ===== bootstrap =====
$ROOT = dirname(__DIR__);
$INIT = $ROOT . '/inc/init.inc.php';
$AUTH = $ROOT . '/inc/auth.inc.php';

if (file_exists($INIT))  require_once $INIT;
if (file_exists($AUTH))  require_once $AUTH;

// Fallbacks if project doesn't define these already
if (!function_exists('h')) {
  function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('current_user_id')) {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  function current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
}
if (!function_exists('db')) {
  // If your project always defines db() in init.inc.php, this won't run.
  // Add a minimal fallback here only if you really need it.
  function db(): PDO {
    throw new RuntimeException('db() not available; include init.inc.php.');
  }
}

// ===== local helpers =====
function nav_active(string $file): string {
  $path = $_SERVER['SCRIPT_NAME'] ?? '';
  return (substr($path, -strlen($file)) === $file) ? 'active' : '';
}
function _db_has_table(PDO $pdo, string $table): bool {
  try {
    $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $q->execute([$table]);
    return (bool)$q->fetchColumn();
  } catch (Throwable $e) { return false; }
}
function _db_has_column(PDO $pdo, string $table, string $col): bool {
  try {
    $q = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->execute([$table, $col]);
    return (bool)$q->fetchColumn();
  } catch (Throwable $e) { return false; }
}

// ===== page title =====
$pageTitle = $pageTitle ?? '';

// ===== user + badges =====
$authName = null;
$badgeReq = 0; // not rendered (kept for future)
$badgePM  = 0; // shown on Messages tab

$uid = (int) current_user_id();
if ($uid) {
  try {
    $pdo = db();

    // name
    $st = $pdo->prepare('SELECT full_name FROM students WHERE student_id=?');
    $st->execute([$uid]);
    $authName = $st->fetchColumn() ?: null;

    // requests unread (not displayed now)
    if (_db_has_table($pdo, 'messages') && _db_has_table($pdo, 'transactions')) {
      $hasReads = _db_has_table($pdo, 'message_reads') && _db_has_column($pdo, 'message_reads', 'last_seen_at');
      if ($hasReads) {
        $q = $pdo->prepare("
          SELECT COUNT(DISTINCT m.transaction_id)
            FROM transactions t
            JOIN messages m ON m.transaction_id = t.transaction_id
            LEFT JOIN message_reads r ON r.transaction_id = t.transaction_id AND r.user_id = ?
           WHERE (t.requester_id = ? OR t.provider_id = ?) AND m.sender_id <> ?
             AND m.created_at > COALESCE(r.last_seen_at, '1970-01-01 00:00:00')
        ");
        $q->execute([$uid,$uid,$uid,$uid]);
      } else {
        $q = $pdo->prepare("
          SELECT COUNT(DISTINCT m.transaction_id)
            FROM transactions t
            JOIN messages m ON m.transaction_id = t.transaction_id
           WHERE (t.requester_id = ? OR t.provider_id = ?) AND m.sender_id <> ?
        ");
        $q->execute([$uid,$uid,$uid]);
      }
      $badgeReq = (int)($q->fetchColumn() ?: 0);
    }

    // PM unread (badge shown on Messages)
    if (_db_has_table($pdo, 'pm_threads') && _db_has_table($pdo, 'pm_messages')) {
      $hasPMReads = _db_has_table($pdo, 'pm_reads') && _db_has_column($pdo, 'pm_reads', 'last_seen_at');
      if ($hasPMReads) {
        $q = $pdo->prepare("
          SELECT COUNT(DISTINCT m.thread_id)
            FROM pm_threads th
            JOIN pm_messages m ON m.thread_id = th.thread_id
            LEFT JOIN pm_reads rd ON rd.thread_id = th.thread_id AND rd.user_id = ?
           WHERE (th.user_one = ? OR th.user_two = ?) AND m.sender_id <> ?
             AND m.created_at > COALESCE(rd.last_seen_at, '1970-01-01 00:00:00')
        ");
        $q->execute([$uid,$uid,$uid,$uid]);
      } else {
        $q = $pdo->prepare("
          SELECT COUNT(DISTINCT m.thread_id)
            FROM pm_threads th
            JOIN pm_messages m ON m.thread_id = th.thread_id
           WHERE (th.user_one = ? OR th.user_two = ?) AND m.sender_id <> ?
        ");
        $q->execute([$uid,$uid,$uid]);
      }
      $badgePM = (int)($q->fetchColumn() ?: 0);
    }
  } catch (Throwable $e) {
    // fail quietly in header
  }
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
  <title><?= $pageTitle ? h($pageTitle) . ' — ' : '' ?>FUSS</title>
</head>
<body>

<header class="masthead">
  <div class="masthead-top container">
    <a class="brand" href="/index.php">
      <img class="brand-mark"
           src="/assets/flinders-mark.svg"
           onerror="this.onerror=null;this.src='/assets/cat/flinders-mark.png';"
           alt="Flinders crest" width="44" height="44" decoding="async" fetchpriority="high">
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
  <a class="btn btn--ghost" href="<?= AUTH_LOGIN ?>">Log in</a>
  <a class="btn" href="<?= AUTH_REGISTER ?>">Sign up</a>
<?php endif; ?>
    </div>
  </div>

  <div class="masthead-nav container">
    <nav class="tabs">
      <a class="<?= nav_active('/index.php') ?>"              href="/index.php">Home</a>
      <a class="<?= nav_active('/browse.php') ?>"             href="/browse.php">Browse Skills</a>
      <a class="<?= nav_active('/messages.php') ?>"           href="/messages.php">Requests</a>
      <a class="<?= nav_active('/recommendations.php') ?>"    href="/recommendations.php">Recommendations</a>
      <a class="<?= nav_active('/profile_zone.php') ?>"       href="/profile_zone.php">My Zone</a>
      <a class="<?= nav_active('/pm/index.php') ?>"           href="/pm/index.php">
        Messages
        <?php if ($badgePM > 0): ?>
          <span class="tab-badge" aria-label="<?= $badgePM ?> unread"><?= $badgePM ?></span>
        <?php endif; ?>
      </a>
      <a class="<?= nav_active('/profile.php') ?>"            href="/profile.php">Profile</a>
    </nav>
    <div class="page-title"><?= h($pageTitle) ?></div>
  </div>
</header>

<main class="container">
