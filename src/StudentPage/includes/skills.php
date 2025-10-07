<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';

/**
 * Add/attach a skill to a student (creates skill row if needed).
 * $role is 'offered' or 'requested'.
 */
function add_student_skill(int $studentId, string $name, string $category, string $description, string $role, string $details=''): void {
  // find existing skill by (name,category) or create
  $st = db()->prepare("SELECT skill_id FROM skills WHERE name=? AND (category <=> ?)");
  $st->execute([$name, $category !== '' ? $category : null]);
  $row = $st->fetch();
  $skillId = $row ? (int)$row['skill_id'] : null;
  if (!$skillId) {
    $ins = db()->prepare("INSERT INTO skills (name, category, description) VALUES (?,?,?)");
    $ins->execute([sanitize($name,100), $category !== '' ? $category : null, sanitize($description,1000)]);
    $skillId = (int)db()->lastInsertId();
  }
  $link = db()->prepare("INSERT INTO student_skills (student_id, skill_id, role, details) VALUES (?,?,?,?)");
  $link->execute([$studentId, $skillId, $role, sanitize($details,1000)]);
}

function delete_student_skill(int $id, int $studentId): void {
  db()->prepare("DELETE FROM student_skills WHERE id=? AND student_id=?")->execute([$id,$studentId]);
}

function get_student_skills(int $studentId, string $role): array {
  $st = db()->prepare("
    SELECT ss.id, s.name, s.category, s.description, ss.details
    FROM student_skills ss JOIN skills s ON s.skill_id = ss.skill_id
    WHERE ss.student_id=? AND ss.role=? ORDER BY s.name ASC
  ");
  $st->execute([$studentId, $role]);
  return $st->fetchAll();
}
