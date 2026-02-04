<?php
session_start();
require_once '../backend/db.php';
$conn = getConn();

// Get only the claims that are already 'Paid'
$tsql = "SELECT c.id, f.full_name, c.approved_payout, c.disbursement_date 
         FROM claims c
         JOIN policies p ON c.policy_id = p.id
         JOIN farmers f ON p.farmer_id = f.id
         WHERE c.status = 'Paid' ORDER BY c.disbursement_date DESC";
$stmt = sqlsrv_query($conn, $tsql);
?>

<h2>✅ Disbursement History</h2>
<table class="table-custom">
    <tr>
        <th>Date Paid</th>
        <th>Farmer</th>
        <th>Amount Released</th>
        <th>Status</th>
    </tr>
    <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
    <tr>
        <td><?php echo $row['disbursement_date']->format('Y-m-d'); ?></td>
        <td><?php echo $row['full_name']; ?></td>
        <td>₱<?php echo number_format($row['approved_payout'], 2); ?></td>
        <td><span class="badge-paid">SUCCESS</span></td>
    </tr>
    <?php endwhile; ?>
</table>