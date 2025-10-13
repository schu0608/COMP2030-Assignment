<?php
require_once dirname(__DIR__).'/inc/init.inc.php';
require_once dirname(__DIR__).'/inc/recommend.inc.php';
$uid = require_login();
$pdo = db();

$onlyMyZone = isset($_GET['myzone']);
$recs = recommend_helpers($pdo, $uid, 12, $onlyMyZone);

include dirname(__DIR__).'/templates/header.php';
?>
<h1>Recommended helpers</h1>

<form method="get" class="bar" style="margin-bottom:12px">
  <label class="flag">
    <input type="checkbox" name="myzone" value="1" <?= $onlyMyZone?'checked':'' ?>>
    <span class="flag-text">Prefer my zone only</span>
  </label>
  <button class="btn btn--sm">Apply</button>
  <a class="btn btn--sm" href="/browse.php">Browse skills</a>
</form>

<div class="stack">
  <?php if (!$recs): ?>
    <p class="notice">No recommendations yet. Add “Skills Requested” to your profile or try browsing.</p>
  <?php else: foreach ($recs as $r): ?>
    <article class="card">
      <div class="grid grid--2">
        <div>
          <div class="muted"><?= htmlspecialchars($r['category']) ?> • score <?= number_format($r['score'], 2) ?></div>
          <h3 style="margin:.2rem 0"><?= htmlspecialchars($r['skill_name']) ?></h3>
          <div class="muted">Helper: <?= htmlspecialchars($r['provider_name']) ?>
            <?php if (!empty($r['zone_name'])): ?>
              • Zone: <?= htmlspecialchars($r['zone_name']) ?>
            <?php endif; ?>
          </div>
        </div>
        <div style="text-align:right">
          <a class="btn btn--sm" href="/thread.php?provider=<?= (int)$r['provider_id'] ?>&skill=<?= (int)$r['skill_id'] ?>">
            Message
          </a>
        </div>
      </div>
    </article>
  <?php endforeach; endif; ?>
</div>

<?php include dirname(__DIR__).'/templates/footer.php'; ?>
