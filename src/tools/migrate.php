<?php
// Run schema tweaks from within Codespaces (CLI): php src/tools/migrate.php
require_once dirname(__DIR__) . '/inc/init.inc.php'; // must provide db()

/** @var PDO $pdo */
$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function apply(PDO $pdo, string $sql, string $label) {
  echo "• $label ... ";
  try {
    $pdo->exec($sql);
    echo "OK\n";
  } catch (Throwable $e) {
    // Not fatal: show message and continue (helps when column already exists)
    echo "SKIP/INFO: " . $e->getMessage() . "\n";
  }
}

echo "== FUSS migration start ==\n";

// 1) zones table
apply($pdo, <<<SQL
CREATE TABLE IF NOT EXISTS zones (
  zone_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL, "Create zones");

// 2) students.zone_id (nullable) + FK
apply($pdo, "ALTER TABLE students ADD COLUMN zone_id INT NULL", "Add students.zone_id");
apply($pdo, "ALTER TABLE students ADD CONSTRAINT fk_students_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)", "Add FK students.zone_id -> zones.zone_id");

// 3) seed a few zones (idempotent)
$zones = ['Hub Central','Flinders Station','Tonsley','Sturt','Bedford Park - Central'];
$stmt = $pdo->prepare("INSERT IGNORE INTO zones (name) VALUES (:n)");
foreach ($zones as $z) {
  $stmt->execute([':n' => $z]);
}
echo "• Seed zones: " . implode(', ', $zones) . " ... OK\n";

// 4) skill_popularity table (used by recommendations; safe to create)
apply($pdo, <<<SQL
CREATE TABLE IF NOT EXISTS skill_popularity (
  skill_id INT PRIMARY KEY,
  uses INT NOT NULL DEFAULT 0,
  last_used TIMESTAMP NULL,
  CONSTRAINT fk_skill_popularity_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL, "Create skill_popularity");

// quick summary counts
$counts = [
  'zones' => (int)$pdo->query("SELECT COUNT(*) FROM zones")->fetchColumn(),
  'students' => (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
  'skills' => (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn(),
];
echo "== Done. Rows: zones={$counts['zones']}, students={$counts['students']}, skills={$counts['skills']} ==\n";
