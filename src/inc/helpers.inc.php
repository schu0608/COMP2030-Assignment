<?php
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function json_out($data, int $code=200): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); }
function redirect(string $path): never { header('Location: '.$path); exit; }

if (!function_exists('is_admin')) {
function is_admin(int $uid): bool {
    try {
      $pdo = db();

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

      $email = '';
      $st = $pdo->prepare('SELECT email FROM students WHERE student_id=?');
      $st->execute([$uid]);
      $email = strtolower((string)$st->fetchColumn());

      $env  = getenv('FUSS_ADMIN_EMAILS') ?: '';
      $list = array_filter(array_map(static fn($s) => strtolower(trim($s)), explode(',', $env)));

      if ($list && $email) {
        return in_array($email, $list, true);
      }

      return $uid === 1;

    } catch (Throwable $e) {
      return false;
    }
  }
}

function mark_thread_seen(int $userId, int $txId): void {
  $pdo = db();
  $sql = "INSERT INTO message_reads (user_id, transaction_id, last_seen_at)
          VALUES (?, ?, NOW())
          ON DUPLICATE KEY UPDATE last_seen_at = GREATEST(last_seen_at, NOW())";
  $pdo->prepare($sql)->execute([$userId, $txId]);
}

function unread_thread_count(int $userId): int {
  $pdo = db();
  $sql = "SELECT COUNT(DISTINCT m.transaction_id) AS c
            FROM transactions t
            JOIN messages m ON m.transaction_id = t.transaction_id
       LEFT JOIN message_reads r
              ON r.transaction_id = m.transaction_id AND r.user_id = ?
           WHERE (t.requester_id = ? OR t.provider_id = ?)
             AND m.sender_id <> ?
             AND m.created_at > COALESCE(r.last_seen_at, '1970-01-01')";
  $st = $pdo->prepare($sql);
  $st->execute([$userId, $userId, $userId, $userId]);
  return (int) $st->fetchColumn();
}