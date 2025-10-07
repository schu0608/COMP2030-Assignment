<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/transactions.php';

require_login(); $uid=current_user_id();
$bal = get_fuss_balance($uid);
$rows = get_transaction_history($uid,100);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Transactions</title></head><body>
<h1>Transaction History</h1>
<p><strong>Current FUSSCredit Balance:</strong> <?= number_format($bal,2) ?></p>
<p><a href="/index.php">Back</a></p>
<table border="1" cellpadding="6" cellspacing="0">
  <tr><th>#</th><th>Role</th><th>Hours</th><th>FUSSCredits (±)</th><th>Status</th></tr>
  <?php foreach ($rows as $r):
    $isProvider = ((int)$r['provider_id'] === $uid);
    $delta = $isProvider ? +$r['fuss_credit_amount'] : -$r['fuss_credit_amount'];
    ?>
    <tr>
      <td><?= (int)$r['transaction_id'] ?></td>
      <td><?= $isProvider ? 'Provided' : 'Requested' ?></td>
      <td><?= number_format((float)$r['hours'],2) ?></td>
      <td style="text-align:right;"><?= number_format((float)$delta,2) ?></td>
      <td><?= htmlspecialchars($r['status']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</body></html>
