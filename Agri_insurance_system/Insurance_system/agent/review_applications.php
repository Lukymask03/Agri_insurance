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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $policy_id = $_POST['application_id'];
    
    if ($action === 'approve') {
        $tsql = "UPDATE insurance_policies SET status = 'active', start_date = GETDATE() WHERE id = ?";
        $stmt = sqlsrv_query($conn, $tsql, array($policy_id));
        if ($stmt) $success = "Policy #$policy_id has been successfully approved.";
    } elseif ($action === 'reject') {
        $tsql = "UPDATE insurance_policies SET status = 'inactive' WHERE id = ?";
        $stmt = sqlsrv_query($conn, $tsql, array($policy_id));
        if ($stmt) $success = "Policy #$policy_id has been rejected.";
    }
}

$pending_applications = [];
$tsql_fetch = "SELECT p.id as application_id, f.full_name as farmer_name, 
               cl.type as item_type, p.policy_type, p.coverage_amount
               FROM insurance_policies p
               JOIN crops_livestock cl ON p.item_id = cl.id
               JOIN farmers f ON cl.farmer_id = f.id
               WHERE p.status = 'pending'";

$stmt_fetch = sqlsrv_query($conn, $tsql_fetch);
if ($stmt_fetch) {
    while ($row = sqlsrv_fetch_array($stmt_fetch, SQLSRV_FETCH_ASSOC)) {
        $pending_applications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Applications - Agent Dashboard</title>
    <style>
        /* BASE STYLES */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f1f5f9; margin: 0; color: #334155; }
        
        /* INTEGRATED VIBRANT BLUE HEADER */
        .navbar { 
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 100%); 
            color: white; 
            padding: 0.8rem 3rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); 
        }
        
        .nav-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.2rem; }
        
        /* SEMI-TRANSPARENT LOGOUT BUTTON */
        .logout-btn { 
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: white; 
            text-decoration: none; 
            padding: 6px 18px; 
            border-radius: 8px; 
            font-size: 0.85rem; 
            font-weight: 600;
            transition: 0.3s; 
        }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.3); border-color: white; }

        .content-container { padding: 2.5rem 5rem; max-width: 1400px; margin: 0 auto; }
        .back-link { color: #2563eb; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: block; margin-bottom: 1.5rem; }
        
        .header-section h1 { font-size: 2.2rem; color: #1e293b; margin: 0; font-weight: 700; }
        .header-section p { color: #94a3b8; margin-top: 5px; margin-bottom: 2rem; }

        /* UNIFIED STAT CARD */
        .summary-card { background: white; width: fit-content; min-width: 180px; padding: 1.2rem 1.5rem; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 3rem; border: 1px solid #e2e8f0; }
        .summary-val { font-size: 2rem; font-weight: 800; color: #1e293b; display: block; line-height: 1; }
        .summary-lab { color: #94a3b8; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.8px; font-weight: 700; margin-top: 8px; display: block; }

        /* APPLICATION CARDS */
        .apps-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem; }
        .app-card { background: white; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.04); padding: 1.8rem; border: 1px solid #f1f5f9; }
        
        .card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .app-id { font-size: 1rem; font-weight: 700; color: #64748b; }
        .status-tag { background: #fef3c7; color: #92400e; padding: 5px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }

        /* ROW DATA STYLING */
        .info-row { display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #f8fafc; }
        .info-row:last-of-type { border-bottom: none; }
        .info-label { color: #94a3b8; font-size: 0.9rem; font-weight: 500; }
        .info-value { color: #1e293b; font-weight: 600; font-size: 0.95rem; }

        /* ACTION BUTTONS */
        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 1.5rem; }
        .btn { border: none; padding: 12px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
        .btn-approve { background: #22c55e; color: white; }
        .btn-reject { background: #ef4444; color: white; }
        .btn:hover { filter: brightness(95%); transform: translateY(-1px); }

        .alert-box { background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid #bbf7d0; font-weight: 500; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">👔 Agent Dashboard</div>
    <div style="display:flex; align-items:center; gap:25px;">
        <div style="text-align: right;">
            <div style="font-size: 0.9rem; font-weight: 700;"><?php echo htmlspecialchars($full_name); ?></div>
            <div style="font-size: 0.7rem; opacity: 0.8;">Insurance Agent</div>
        </div>
    </div>
</nav>

<div class="content-container">
    <a href="dashboard.php" class="back-link">← Back to Home</a>

    <div class="header-section">
        <h1>Review Applications</h1>
        <p>Manage and verify pending insurance applications from the database.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert-box">✅ <?php echo $success; ?></div>
    <?php endif; ?>

    <div class="summary-card">
        <span class="summary-val"><?php echo count($pending_applications); ?></span>
        <span class="summary-lab">Pending Items</span>
    </div>

    <div class="apps-grid">
        <?php foreach($pending_applications as $app): ?>
        <div class="app-card">
            <div class="card-top">
                <span class="app-id">#APP-<?php echo $app['application_id']; ?></span>
                <span class="status-tag">PENDING REVIEW</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">👤 Farmer Name</span>
                <span class="info-value"><?php echo htmlspecialchars($app['farmer_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🌾 Category</span>
                <span class="info-value"><?php echo htmlspecialchars($app['item_type']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🛡️ Policy Type</span>
                <span class="info-value"><?php echo htmlspecialchars($app['policy_type']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">💰 Coverage</span>
                <span class="info-value">₱<?php echo number_format($app['coverage_amount'], 2); ?></span>
            </div>

            <div class="btn-group">
                <form method="POST" style="display:contents;">
                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-approve">Approve</button>
                </form>
                <form method="POST" style="display:contents;">
                    <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-reject">Reject</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>