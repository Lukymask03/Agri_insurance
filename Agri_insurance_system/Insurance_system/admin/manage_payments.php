<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); // Updated for SQLSRV
$payments = [];
$total_payout = 0;
$error = '';

try {
    $query = "SELECT pay.*, f.full_name, c.id as claim_id, p.policy_type 
              FROM payments pay
              JOIN claims c ON pay.claim_id = c.id
              JOIN insurance_policies p ON c.policy_id = p.id
              JOIN crops_livestock cl ON p.item_id = cl.id
              JOIN farmers f ON cl.farmer_id = f.id
              ORDER BY pay.payment_date DESC";
    
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $payments[] = $row;
        $total_payout += ($row['amount'] ?? 0);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Payments - Agri Insurance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .table th { text-align: left; padding: 14px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
        .table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .stat-banner { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; border-left: 5px solid #10b981; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .amount-positive { color: #10b981; font-weight: bold; }
        .method-tag { background: #f1f5fe; color: #7c3aed; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .btn-dash { color: white; border: 1px solid rgba(255,255,255,0.6); padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; }
    </style>
</head>
<body>

<div class="header">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 1.5rem;">💰</span>
        <h2 style="margin: 0;">Payment Records</h2>
    </div>
    <a href="dashboard.php" class="btn-dash">← Dashboard</a>
</div>

<div class="container">
    <div class="stat-banner">
        <div>
            <span style="color: #64748b; font-size: 0.9rem;">Total Disbursed Funds</span>
            <h2 style="margin: 5px 0 0 0; color: #1e293b;">₱<?php echo number_format($total_payout, 2); ?></h2>
        </div>
        <div class="method-tag">SYSTEM LIVE</div>
    </div>

    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b;">Recent Payouts</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Farmer</th>
                    <th>Policy Type</th>
                    <th>Claim ID</th>
                    <th>Amount Paid</th>
                    <th>Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 50px; color: #94a3b8;">No payment records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td style="font-family: monospace; color: #64748b;">#PAY-<?php echo $pay['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($pay['full_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pay['policy_type']); ?></td>
                        <td>#CLM-<?php echo $pay['claim_id']; ?></td>
                        <td class="amount-positive">₱<?php echo number_format($pay['amount'], 2); ?></td>
                        <td><span class="method-tag"><?php echo strtoupper($pay['payment_method'] ?? 'Transfer'); ?></span></td>
                        <td>
                            <?php 
                                $date = $pay['payment_date'] ?? $pay['created_at'];
                                echo ($date instanceof DateTime) ? $date->format('M d, Y') : date('M d, Y', strtotime($date));
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>