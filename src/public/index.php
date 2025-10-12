<?php
require_once __DIR__ . '/../inc/init.inc.php';

include __DIR__ . '/../templates/header.php';

$uid = current_user_id();
$balance = null;
if ($uid) {
  try {
    $st = db()->prepare('SELECT fuss_credits FROM students WHERE student_id=?');
    $st->execute([$uid]);
    $balance = (float)$st->fetchColumn();
  } catch (Throwable $e) { /* ignore */ }
}

/* Small site stats (best-effort) */
$stats = ['members'=>null,'offers'=>null,'requests'=>null,'transactions'=>null];
try {
  $stats['members']      = (int)db()->query("SELECT COUNT(*) FROM students WHERE active=1")->fetchColumn();
  $stats['offers']       = (int)db()->query("SELECT COUNT(*) FROM student_skills WHERE role='offered'")->fetchColumn();
  $stats['requests']     = (int)db()->query("SELECT COUNT(*) FROM student_skills WHERE role='requested'")->fetchColumn();
  $stats['transactions'] = (int)db()->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
} catch (Throwable $e) { /* ignore */ }

/* Featured categories (top 6) */
$cats = [];
try {
  $cats = db()->query("SELECT category, COUNT(*) c
                         FROM skills
                        WHERE category IS NOT NULL AND category <> ''
                        GROUP BY category
                        ORDER BY c DESC
                        LIMIT 6")->fetchAll();
} catch (Throwable $e) {}

/* Latest offers (6) */
$offers = [];
try {
  $sql = 'SELECT ss.id offer_id, s.name, s.category, st.full_name
            FROM student_skills ss
            JOIN skills s   ON s.skill_id = ss.skill_id
            JOIN students st ON st.student_id = ss.student_id
           WHERE ss.role = "offered"
           ORDER BY ss.id DESC
           LIMIT 6';
  $stmt = db()->prepare($sql); $stmt->execute(); $offers = $stmt->fetchAll();
} catch (Throwable $e) {}

/* Latest requested (6) */
$reqs = [];
try {
  $sql = 'SELECT ss.id request_id, s.name, s.category, st.full_name, st.student_id requester_id
            FROM student_skills ss
            JOIN skills s   ON s.skill_id = ss.skill_id
            JOIN students st ON st.student_id = ss.student_id
           WHERE ss.role = "requested"
           ORDER BY ss.id DESC
           LIMIT 6';
  $stmt = db()->prepare($sql); $stmt->execute(); $reqs = $stmt->fetchAll();
} catch (Throwable $e) {}
?>

<!-- HERO -->
<section class="container" style="margin-top:24px;margin-bottom:28px">
  <div class="filter-card" style="display:grid;grid-template-columns:1.3fr 1fr;gap:24px;align-items:center">
    <div>
      <h1 style="margin:0 0 10px;font-size:2.2rem;font-weight:800">
        Trade skills. Earn <span style="color:var(--accent)">FUSSCredits</span>. Build community.
      </h1>
      <p class="muted" style="margin:0 0 16px">
        FUSS helps Flinders students exchange tutoring, feedback, coding help, moving assistance, language practice and more—no money, just time.
      </p>

      <div class="search-inline" style="display:flex;gap:10px;align-items:center">
        <form method="get" action="/browse.php" style="display:flex;gap:10px;flex:1">
          <input name="q" placeholder="Search skills or people…" aria-label="Search">
          <button class="btn btn--primary">Search</button>
        </form>
        <?php if ($uid): ?>
          <a class="btn" href="/messages.php">Requests</a>
          <a class="btn" href="/profile.php">Your profile</a>
        <?php else: ?>
          <a class="btn" href="/auth/login.php">Log in</a>
          <a class="btn btn--primary" href="/auth/register.php">Create account</a>
        <?php endif; ?>
      </div>

      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:14px" class="muted">
        <?php if ($stats['members'] !== null): ?>
          <span>👥 <?= (int)$stats['members'] ?> students</span>
        <?php endif; ?>
        <?php if ($stats['offers'] !== null): ?>
          <span>🛠️ <?= (int)$stats['offers'] ?> offers</span>
        <?php endif; ?>
        <?php if ($stats['requests'] !== null): ?>
          <span>🔎 <?= (int)$stats['requests'] ?> requests</span>
        <?php endif; ?>
        <?php if ($stats['transactions'] !== null): ?>
          <span>⇄ <?= (int)$stats['transactions'] ?> exchanges</span>
        <?php endif; ?>
        <?php if ($uid && $balance !== null): ?>
          <span class="credit-pill">Your balance: <?= number_format($balance, 0) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="notice" style="display:grid;gap:8px">
      <strong>How it works</strong>
      <div class="grid grid--2">
        <div>
          <div>① <strong>List a skill</strong></div>
          <div class="muted">e.g., COMP1002 tutoring, proofreading, design help.</div>
        </div>
        <div>
          <div>② <strong>Request help</strong></div>
          <div class="muted">Message, negotiate time, confirm hours.</div>
        </div>
        <div>
          <div>③ <strong>Earn & spend credits</strong></div>
          <div class="muted">1 hour = 1 FUSSCredit.</div>
        </div>
        <div>
          <div>④ <strong>Review peers</strong></div>
          <div class="muted">Keep quality high with ratings.</div>
        </div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
        <a class="btn btn--primary" href="/browse.php">Browse skills</a>
        <?php if ($uid): ?>
          <a class="btn" href="/profile_edit.php">List a skill</a>
        <?php else: ?>
          <a class="btn" href="/auth/register.php">Join FUSS</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- FEATURED CATEGORIES -->
<section class="container" style="margin-bottom:28px">
  <h2 style="margin:0 0 12px">Popular categories</h2>
  <div style="display:flex;flex-wrap:wrap;gap:10px">
    <?php if ($cats): foreach ($cats as $c): ?>
      <a class="btn btn--pill" href="/browse.php?category=<?= urlencode($c['category']) ?>">
        <?= h($c['category']) ?>
      </a>
    <?php endforeach; else: ?>
      <span class="muted">Categories will appear as students add skills.</span>
    <?php endif; ?>
  </div>
</section>

<!-- LATEST OFFERS -->
<section class="container" style="margin-bottom:8px">
  <h2 style="margin:0 0 12px">Latest offers</h2>
  <div class="card-grid">
    <?php if ($offers): foreach ($offers as $r): ?>
      <a class="skill-card" href="/skill.php?id=<?= (int)$r['offer_id'] ?>">
        <div class="thumb"></div>
        <div class="meta">
          <div class="provider"><?= h($r['full_name']) ?></div>
          <div class="title"><?= h($r['name']) ?></div>
          <div class="sub"><?= h($r['category']) ?></div>
        </div>
      </a>
    <?php endforeach; else: ?>
      <div class="notice">No offers yet — be the first to <a href="<?= $uid ? '/profile_edit.php' : '/auth/register.php' ?>">list a skill</a>!</div>
    <?php endif; ?>
  </div>
</section>

<!-- LATEST REQUESTS -->
<section class="container" style="margin-bottom:40px">
  <h2 style="margin:0 0 12px">Students looking for…</h2>
  <div class="card-grid">
    <?php if ($reqs): foreach ($reqs as $r): ?>
      <article class="skill-card">
        <div class="thumb"></div>
        <div class="meta">
          <div class="provider"><?= h($r['full_name']) ?></div>
          <div class="title"><?= h($r['name']) ?></div>
          <div class="sub"><?= h($r['category']) ?> • requested</div>
          <div style="margin-top:8px;display:flex;gap:8px;align-items:center">
            <a class="btn" href="/browse.php?category=<?= urlencode($r['category']) ?>">See similar</a>
            <?php if ($uid): ?>
              <form method="post" action="/actions/request_offer.php" style="display:flex;gap:8px;align-items:center">
                <?= csrf_field() ?>
                <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                <label class="muted" style="font-weight:600">Hours
                  <input name="hours" type="number" min="0.5" step="0.5" value="1" required style="width:72px;margin-left:6px">
                </label>
                <button class="btn btn--primary">Offer to help</button>
              </form>
            <?php else: ?>
              <a class="btn btn--primary" href="/auth/login.php">Log in to offer</a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; else: ?>
      <div class="notice">No requests yet — try <a href="/browse.php">browsing skills</a> or <a href="<?= $uid ? '/profile_edit.php' : '/auth/register.php' ?>">add your own request</a>.</div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
