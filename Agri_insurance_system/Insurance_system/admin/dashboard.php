<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

// Check if user is logged in and is admin
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$username = $_SESSION['username'] ?? 'admin';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';

// Fetching real-time stats from view_dashboard_stats
$view_sql = "SELECT * FROM view_dashboard_stats";
$view_stmt = sqlsrv_query($conn, $view_sql);

if ($view_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$db_data = sqlsrv_fetch_array($view_stmt, SQLSRV_FETCH_ASSOC);

$stats = [
    'total_users'      => 2004, 
    'total_farmers'    => $db_data['total_farmers'] ?? 0,
    'total_items'      => $db_data['total_items'] ?? 0,
    'active_policies'  => $db_data['active_policies'] ?? 0,
    'total_coverage'   => $db_data['total_coverage'] ?? 0,
    'pending_claims'   => $db_data['pending_claims'] ?? 0,
    'total_revenue'    => $db_data['total_revenue'] ?? 0,
    'total_disbursed'  => $db_data['total_disbursed'] ?? 0 
];

// ... (Rest of your SQL logic for pending claims, alerts, activities remains the same) ...

$pending_list_sql = "SELECT TOP 5 c.id, f.full_name, c.claim_type, c.estimated_loss 
                     FROM claims c 
                     JOIN insurance_policies p ON c.policy_id = p.id 
                     JOIN farmers f ON p.farmer_id = f.id 
                     WHERE c.status = 'Pending' 
                     ORDER BY c.id DESC";
$pending_list_stmt = sqlsrv_query($conn, $pending_list_sql);

$alerts = [];
if ($stats['pending_claims'] > 0) {
    $alerts[] = ['level' => 'warning', 'message' => 'New claims require your immediate attention.', 'count' => $stats['pending_claims']];
}
$alerts[] = ['level' => 'info', 'message' => 'System performing optimally.', 'count' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Agri-Insurance</title>
    <style>
        /* (Your CSS remains the same) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        .navbar { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { max-width: 1600px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: relative; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #7c3aed; }
        .alerts-section { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .alert-item { padding: 1rem; margin-bottom: 0.5rem; border-radius: 5px; }
        .alert-item.warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        .action-table { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 8px; }
        .action-table th { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; font-size: 0.8rem; }
        .action-table td { padding: 10px; border-bottom: 1px solid #eee; font-size: 0.85rem; }
        .btn-action { padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: bold; }
        .btn-approve { background: #7c3aed; color: white; }
        .btn-reject { background: #ef4444; color: white; margin-left: 5px; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .action-btn { background: white; padding: 1.5rem; border-radius: 10px; text-align: center; text-decoration: none; color: #7c3aed; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .btn-logout-ui { background: rgba(255,255,255,0.2); color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; border: 1px solid white; }
        .btn-logout-ui:hover { background: white; color: #7c3aed; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">⚙️ Admin Dashboard</div>
        <div class="navbar-user">
             <span class="user-name" style="margin-right: 15px;"><?php echo htmlspecialchars($full_name); ?></span>
             <a href="javascript:void(0)" onclick="confirmLogout()" class="btn-logout-ui">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Pending Claims</div>
                <div class="value"><?php echo $stats['pending_claims']; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Disbursed</div>
                <div class="value">₱<?php echo number_format($stats['total_disbursed'], 2); ?></div>
            </div>
        </div>

        <div class="alerts-section">
            <h2>⚠️ System Alerts</h2>
            <?php foreach($alerts as $alert): ?>
                <div class="alert-item <?php echo $alert['level']; ?>">
                    <strong><?php echo htmlspecialchars($alert['message']); ?></strong>
                    
                    <?php if($alert['level'] == 'warning' && $stats['pending_claims'] > 0): ?>
                        <table class="action-table">
                            <thead>
                                <tr>
                                    <th>Farmer</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($claim = sqlsrv_fetch_array($pending_list_stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($claim['full_name']); ?></td>
                                    <td><?php echo $claim['claim_type']; ?></td>
                                    <td>₱<?php echo number_format($claim['estimated_loss'], 2); ?></td>
                                    <td>
                                        <a href="../adjuster/approve_amount.php?id=<?php echo $claim['id']; ?>" class="btn-action btn-approve">Verify</a>
                                        <a href="reject_claim.php?id=<?php echo $claim['id']; ?>" class="btn-action btn-reject" onclick="return confirm('Reject Claim?')">Reject</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="quick-actions">
            <a href="manage_claims.php" class="action-btn">📝 Claims</a>
            <a href="settlements.php" class="action-btn">💰 Settlements</a>
            <a href="reports.php" class="action-btn">📊 Reports</a>
        </div>
    </div>

    <script>
    function confirmLogout() {
        if (confirm("Are you sure you want to log out from the Admin Panel?")) {
            window.location.href = '../backend/auth.php?action=logout';
        }
    }
    </script>
</body>
</html>