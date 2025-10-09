<?php require_once __DIR__."/../inc/dbconn.inc.php"; ?>
<?php
// ---------- metrics ----------
$activeStudents = 0;
$activeServices = 0;
$avgCredits     = 0.0;
$popular        = [];

// Active students
if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM students WHERE active=1")) {
  if ($row = mysqli_fetch_assoc($res)) $activeStudents = (int)$row['c'];
}

// Active services (transactions in flight)
if ($res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM transactions WHERE status IN ('pending','confirmed')")) {
  if ($row = mysqli_fetch_assoc($res)) $activeServices = (int)$row['c'];
}

// Average credits
if ($res = mysqli_query($conn, "SELECT ROUND(AVG(fuss_credits),2) AS avgc FROM students")) {
  if ($row = mysqli_fetch_assoc($res)) $avgCredits = (float)($row['avgc'] ?? 0);
}

// Popular skills (top 3 offered)
$sql = "
  SELECT s.name, COUNT(*) AS total
  FROM student_skills ss
  JOIN skills s ON s.skill_id = ss.skill_id
  WHERE ss.role='offered'
  GROUP BY s.name
  ORDER BY total DESC
  LIMIT 3
";
if ($res = mysqli_query($conn, $sql)) {
  while ($row = mysqli_fetch_assoc($res)) $popular[] = $row['name'];
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin • Dashboard</title>
  <style>
    :root{
      --bg:#0f172a;        /* slate-900 */
      --card:#111827;      /* gray-900 */
      --mut:#94a3b8;       /* slate-400 */
      --text:#e5e7eb;      /* gray-200 */
      --accent:#8b5cf6;    /* violet-500 */
      --accent-2:#22c55e;  /* green-500 */
      --accent-3:#f59e0b;  /* amber-500 */
      --ring:#1f2937;      /* gray-800 */
      --chip:#1f2937;
    }
    *{box-sizing:border-box}
    body{
      margin:0; padding:32px;
      background:linear-gradient(180deg,#0b1220, #0f172a);
      color:var(--text); font-family:system-ui,Segoe UI,Arial,sans-serif;
    }
    .container{max-width:1100px;margin:0 auto}
    header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    h1{margin:0;font-size:28px;letter-spacing:.3px}
    .sub{margin:6px 0 0;color:var(--mut);font-size:14px}
    .grid{
      display:grid; gap:16px;
      grid-template-columns: repeat(12, 1fr);
    }
    .card{
      background:var(--card); border:1px solid var(--ring);
      border-radius:16px; padding:18px;
      box-shadow: 0 10px 30px rgba(0,0,0,.25);
    }
    .stat{grid-column: span 4;}
    .stat .label{color:var(--mut);font-size:13px}
    .stat .value{font-size:32px;font-weight:700;margin-top:6px}
    .accent-1 .value{color:var(--accent)}
    .accent-2 .value{color:var(--accent-2)}
    .accent-3 .value{color:var(--accent-3)}

    .list{grid-column: span 12;}
    .chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
    .chip{
      background:var(--chip); border:1px solid var(--ring);
      padding:6px 10px; border-radius:999px; font-size:13px; color:#cbd5e1;
    }

    .actions{grid-column: span 12; display:grid; gap:12px; grid-template-columns: repeat(4, 1fr)}
    .btn{
      display:block; text-align:center; padding:12px 14px;
      border-radius:12px; background:#111827; border:1px solid var(--ring);
      color:#e5e7eb; text-decoration:none; font-weight:600; transition:.15s transform,.15s background;
    }
    .btn:hover{transform:translateY(-1px); background:#0f172a}
    .btn:active{transform:translateY(0)}
    .btn svg{vertical-align:-3px;margin-right:6px}
    .foot{margin-top:18px;color:var(--mut);font-size:12px;text-align:right}
    @media (max-width:800px){
      .stat{grid-column: span 12;}
      .actions{grid-template-columns: repeat(2, 1fr)}
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div>
        <h1>Admin Dashboard</h1>
        <p class="sub">System snapshot of students, services, credits, and trending skills.</p>
      </div>
    </header>

    <!-- Stats -->
    <section class="grid">
      <div class="card stat accent-1">
        <div class="label">Active Students</div>
        <div class="value"><?= $activeStudents ?></div>
      </div>
      <div class="card stat accent-2">
        <div class="label">Active Services</div>
        <div class="value"><?= $activeServices ?></div>
      </div>
      <div class="card stat accent-3">
        <div class="label">Average FUSS Credits</div>
        <div class="value"><?= number_format($avgCredits, 2) ?></div>
      </div>

      <div class="card list">
        <div class="label">Most Popular Skills (offered)</div>
        <div class="chips">
          <?php if (!empty($popular)): ?>
            <?php foreach ($popular as $name): ?>
              <span class="chip"><?= htmlspecialchars($name) ?></span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="chip">—</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="actions">
        <a class="btn card" href="students.php">
          <!-- user icon -->
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z"/></svg>
          Student Management
        </a>
        <a class="btn card" href="credits.php">
          <!-- credit icon -->
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M3 7h18a2 2 0 0 1 2 2v6H1V9a2 2 0 0 1 2-2Zm20 10H1v1a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2ZM3 9v2h18V9Z"/></svg>
          Credit Adjustment
        </a>
        <a class="btn card" href="skills.php">
          <!-- tag icon -->
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M21.41 11.58 12.42 2.59A2 2 0 0 0 11 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 .59 1.41l8.99 8.99a2 2 0 0 0 2.83 0l6-6a2 2 0 0 0 0-2.82ZM6.5 7A1.5 1.5 0 1 1 8 8.5 1.5 1.5 0 0 1 6.5 7Z"/></svg>
          Skills & Categories
        </a>
        <a class="btn card" href="moderation.php">
          <!-- shield icon -->
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5Zm-1 15-4-4 1.41-1.41L11 13.17l4.59-4.58L17 10Z"/></svg>
          Content Moderation
        </a>
      </div>
    </section>

  </div>
</body>
</html>
