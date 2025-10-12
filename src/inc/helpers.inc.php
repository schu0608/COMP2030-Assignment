<?php
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function json_out($data, int $code=200): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); }
function redirect(string $path): never { header('Location: '.$path); exit; }

if (!function_exists('is_admin')) {
  /**
   * Decide if a user is an admin.
   * Priority:
   *   1) students.is_admin column (if it exists)
   *   2) FUSS_ADMIN_EMAILS env var (comma-separated emails)
   *   3) Fallback: first account (ID 1)
   */
  function is_admin(int $uid): bool {
    try {
      $pdo = db();

      // 1) If you later add a `students.is_admin` column, use it automatically.
      $hasCol = (int)$pdo->query("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'students'
          AND COLUMN_NAME  = 'is_admin'
      ")->fetchColumn();

      if ($hasCol) {
        $st = $pdo->prepare('SELECT is_admin FROM students WHERE student_id=?');
        $st->execute([$uid]);
        return (bool)$st->fetchColumn();
      }

      // 2) Check env list of admin emails
      $email = '';
      $st = $pdo->prepare('SELECT email FROM students WHERE student_id=?');
      $st->execute([$uid]);
      $email = strtolower((string)$st->fetchColumn());

      $env  = getenv('FUSS_ADMIN_EMAILS') ?: '';
      $list = array_filter(array_map(static fn($s) => strtolower(trim($s)), explode(',', $env)));

      if ($list && $email) {
        return in_array($email, $list, true);
      }

      // 3) Fallback: first account is admin
      return $uid === 1;

    } catch (Throwable $e) {
      return false;
    }
  }
}
