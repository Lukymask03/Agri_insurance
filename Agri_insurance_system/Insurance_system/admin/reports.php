<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

// Security: Admin access only
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$error = '';

$total_farmers = 0;
$total_policies = 0;
$total_value = 0;
$claims_stats = ['APPROVED' => 0, 'PENDING' => 0, 'REJECTED' => 0, 'PAID' => 0];
$payouts = [];

try {
    // 1. Summary Stats
    $res_farmers = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM farmers");
    if ($res_farmers) {
        $row = sqlsrv_fetch_array($res_farmers, SQLSRV_FETCH_ASSOC);
        $total_farmers = $row['total'] ?? 0;
    }

    $res_policies = sqlsrv_query($conn, "SELECT COUNT(*) as total FROM insurance_policies");
    if ($res_policies) {
        $row = sqlsrv_fetch_array($res_policies, SQLSRV_FETCH_ASSOC);
        $total_policies = $row['total'] ?? 0;
    }

    $res_value = sqlsrv_query($conn, "SELECT SUM(coverage_amount) as total FROM insurance_policies");
    if ($res_value) {
        $row = sqlsrv_fetch_array($res_value, SQLSRV_FETCH_ASSOC);
        $total_value = $row['total'] ?? 0;
    }
    
    // 2. Claim Statistics (Normalize keys to uppercase)
    $res_claims = sqlsrv_query($conn, "SELECT status, COUNT(*) as count FROM claims GROUP BY status");
    if ($res_claims) {
        while ($row = sqlsrv_fetch_array($res_claims, SQLSRV_FETCH_ASSOC)) {
            $status_key = strtoupper(trim($row['status']));
            $claims_stats[$status_key] = $row['count'];
        }
    }
    
    // 3. Monthly Payout Trend
    $payout_query = "SELECT TOP 6 
                        FORMAT(payment_date, 'MMM yyyy') as month_label, 
                        SUM(amount) as total,
                        MAX(payment_date) as sort_date
                     FROM payments 
                     GROUP BY FORMAT(payment_date, 'MMM yyyy')
                     ORDER BY sort_date DESC";
    
    $res_payouts = sqlsrv_query($conn, $payout_query);
    if ($res_payouts) {
        while ($p_row = sqlsrv_fetch_array($res_payouts, SQLSRV_FETCH_ASSOC)) {
            $payouts[] = $p_row;
        }
    }

} catch (Exception $e) {
    $error = "Reporting Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Reports - Agri Insurance</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; margin: 0; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { padding: 2rem; max-width: 1100px; margin: auto; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #7c3aed; }
        .stat-card h3 { margin: 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #1e293b; margin-top: 10px; }
        
        .chart-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { text-align: left; padding: 12px; background: #f1f5f9; color: #7c3aed; font-size: 0.9rem; }
        .table td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        
        .btn-action { background: rgba(255,255,255,0.2); color: white; border: 1px solid white; padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; cursor: pointer; }
        .btn-action:hover { background: rgba(255,255,255,0.3); }

        @media print {
            .header, .btn-action { display: none; }
            body { background: white; }
            .container { padding: 0; width: 100%; }
            .stat-card { border: 1px solid #ddd; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <h2 style="margin:0;">📊 Executive Summary Reports</h2>
    <div>
        <button onclick="window.print()" class="btn-action">Print PDF Report</button>
        <a href="dashboard.php" class="btn-action" style="margin-left:10px;">Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <?php if ($error): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border-left:5px solid #ef4444;">
            <strong>Database Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="report-grid">
        <div class="stat-card">
            <h3>Total Registered Farmers</h3>
            <div class="value"><?php echo number_format($total_farmers); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Policies</h3>
            <div class="value"><?php echo number_format($total_policies); ?></div>
        </div>
        <div class="stat-card" style="border-top-color: #10b981;">
            <h3>Total Value Insured</h3>
            <div class="value">₱<?php echo number_format($total_value, 2); ?></div>
        </div>
    </div>

    <div class="report-grid">
        <div class="chart-container">
            <h3 style="margin-top:0; color:#7c3aed; border-bottom:1px solid #eee; padding-bottom:10px;">Claim Status Breakdown</h3>
            <table class="table">
                <tr><td>✅ Approved</td><td style="text-align:right;"><strong><?php echo $claims_stats['APPROVED'] ?? 0; ?></strong></td></tr>
                <tr><td>⏳ Pending</td><td style="text-align:right;"><strong><?php echo $claims_stats['PENDING'] ?? 0; ?></strong></td></tr>
                <tr><td>❌ Rejected</td><td style="text-align:right;"><strong><?php echo $claims_stats['REJECTED'] ?? 0; ?></strong></td></tr>
                <tr><td>💰 Paid</td><td style="text-align:right;"><strong><?php echo $claims_stats['PAID'] ?? 0; ?></strong></td></tr>
            </table>
        </div>

        <div class="chart-container">
            <h3 style="margin-top:0; color:#7c3aed; border-bottom:1px solid #eee; padding-bottom:10px;">Recent Payout Trends</h3>
            <table class="table">
                <thead>
                    <tr><th>Month</th><th style="text-align:right;">Amount Disbursed</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($payouts)): ?>
                        <tr><td colspan="2" style="text-align:center; padding: 20px; color:#94a3b8;">No payout history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payouts as $p): ?>
                        <tr>
                            <td><?php echo $p['month_label']; ?></td>
                            <td style="text-align:right; color:#10b981; font-weight:bold;">₱<?php echo number_format($p['total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>