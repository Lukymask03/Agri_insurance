<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Agent';
$username = $_SESSION['username'] ?? 'Agent';

/**
 * FETCH REAL STATS
 */
$tsql_stats = "SELECT * FROM view_dashboard_stats";
$stmt_stats = sqlsrv_query($conn, $tsql_stats);
$db_stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);

$stats = [
    'total_policies' => $db_stats['active_policies'] ?? 0, 
    'active_policies' => $db_stats['active_policies'] ?? 0,
    'pending_applications' => $db_stats['pending_claims'] ?? 0,
    'total_premium_collected' => $db_stats['total_revenue'] ?? 0
];

/**
 * FETCH REAL RECENT POLICIES
 */
$tsql_recent = "SELECT TOP 5 policy_id, farmer_name, policy_type, coverage_amount as premium, start_date, end_date 
                FROM view_active_policies 
                ORDER BY policy_id DESC";
$stmt_recent = sqlsrv_query($conn, $tsql_recent);

$recent_policies = [];
while ($row = sqlsrv_fetch_array($stmt_recent, SQLSRV_FETCH_ASSOC)) {
    $row['status'] = 'active'; 
    $recent_policies[] = $row;
}

/**
 * FETCH REAL FARMERS
 */
$tsql_farmers = "SELECT TOP 5 id as farmer_id, full_name as name, address as location, contact_number as contact 
                 FROM farmers 
                 ORDER BY id DESC";
$stmt_farmers = sqlsrv_query($conn, $tsql_farmers);

$farmers_list = [];
while ($row = sqlsrv_fetch_array($stmt_farmers, SQLSRV_FETCH_ASSOC)) {
    $row['active_policies'] = 1; 
    $row['farm_size'] = 'N/A';   
    $farmers_list[] = $row;
}

$pending_applications = []; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - Agricultural Insurance System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .navbar { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; }
        .navbar-user { display: flex; align-items: center; gap: 1.5rem; }
        .user-info { text-align: right; }
        .user-name { font-weight: 600; display: block; }
        .user-role { font-size: 0.85rem; opacity: 0.9; }
        .logout-link { color: #ffcfcf; text-decoration: none; font-weight: 600; cursor: pointer; padding: 5px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 5px; transition: 0.3s; }
        .logout-link:hover { background: rgba(255,255,255,0.1); color: white; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .welcome-section { background: white; padding: 2rem; border-radius: 10px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .welcome-section h1 { color: #1e3a8a; margin-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #1e3a8a; }
        
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .action-btn { background: white; padding: 1.5rem; border-radius: 10px; text-align: center; text-decoration: none; color: #1e3a8a; font-weight: 600; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s; border: 2px solid transparent; }
        .action-btn:hover { border-color: #3b82f6; transform: translateY(-3px); }
        
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .content-section { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        table th { background: #f3f4f6; padding: 0.75rem; text-align: left; font-size: 0.875rem; }
        table td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">👔 Agent Dashboard</div>
        <div class="navbar-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                <span class="user-role">Insurance Agent</span>
            </div>
            <a href="javascript:void(0)" onclick="confirmAgentLogout()" class="logout-link">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h1>
            <p>Manage insurance policies, review applications, and support farmers.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div style="font-size: 0.9rem; color: #666;">Total Policies</div>
                <div class="value"><?php echo number_format($stats['total_policies']); ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.9rem; color: #666;">Active Policies</div>
                <div class="value"><?php echo number_format($stats['active_policies']); ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.9rem; color: #666;">Pending Applications</div>
                <div class="value"><?php echo number_format($stats['pending_applications']); ?></div>
            </div>
            <div class="stat-card">
                <div style="font-size: 0.9rem; color: #666;">Premium Collected</div>
                <div class="value">₱<?php echo number_format($stats['total_premium_collected']); ?></div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="manage_policies.php" class="action-btn">Manage Policies</a>
            <a href="review_applications.php" class="action-btn">Review Applications</a>
            <a href="manage_farmers.php" class="action-btn">View Farmers</a>
            <a href="create_policy.php" class="action-btn">Create Policy</a>
        </div>

        <div class="content-grid">
            <div class="content-section">
                <h2 style="color: #1e3a8a;">Recent Policies</h2>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Farmer</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_policies as $policy): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($policy['policy_id']); ?></td>
                            <td><?php echo htmlspecialchars($policy['farmer_name']); ?></td>
                            <td><span class="badge"><?php echo ucfirst($policy['status']); ?></span></td>
                            <td><a href="view_policy.php?id=<?php echo $policy['policy_id']; ?>" style="color: #3b82f6; text-decoration: none;">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-section">
                <h2 style="color: #1e3a8a;">Pending Review</h2>
                <?php if(empty($pending_applications)): ?>
                    <p style="margin-top: 1rem; color: #666;">No pending applications to review.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>App ID</th><th>Farmer</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($pending_applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['application_id']); ?></td>
                                <td><?php echo htmlspecialchars($app['farmer_name']); ?></td>
                                <td><button onclick="approveApplication('<?php echo $app['application_id']; ?>')" style="background: #10b981; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Approve</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function confirmAgentLogout() {
            if (confirm("Are you sure you want to log out from the Agent Panel?")) {
                // Correct path: Go up to root, then into backend/auth.php
                window.location.href = '../backend/auth.php?action=logout';
            }
        }

        function approveApplication(appId) {
            if (confirm('Approve application #' + appId + '?')) {
                alert('Application approved successfully!');
                location.reload();
            }
        }
    </script>
</body>
</html>