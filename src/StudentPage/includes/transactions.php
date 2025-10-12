<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Balance for a student from transactions table (read-only for Part 1).
 * Positive when they provided service, negative when requested.
 */
function get_fuss_balance(int $studentId): float {
  $st = db()->prepare("
    SELECT COALESCE(SUM(CASE
      WHEN provider_id = ? THEN fuss_credit_amount
      WHEN requester_id = ? THEN -fuss_credit_amount
      ELSE 0 END), 0) AS bal
    FROM transactions WHERE status IN ('confirmed','completed','settled')
  ");
  $st->execute([$studentId,$studentId]);
  $row = $st->fetch();
  return (float)($row['bal'] ?? 0.0);
}

function get_transaction_history(int $studentId, int $limit=50): array {
  $st = db()->prepare("
    SELECT t.transaction_id, t.requester_id, t.provider_id, t.skill_id, t.hours, t.fuss_credit_amount, t.status
    FROM transactions t
    WHERE t.requester_id = ? OR t.provider_id = ?
    ORDER BY t.transaction_id DESC
    LIMIT ?
  ");
  $st->bindValue(1,$studentId,PDO::PARAM_INT);
  $st->bindValue(2,$studentId,PDO::PARAM_INT);
  $st->bindValue(3,$limit,PDO::PARAM_INT);
  $st->execute();
  return $st->fetchAll();
}
