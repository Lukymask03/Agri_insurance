<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

if (!isLoggedIn() || !hasRole('farmer')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0;

// 1. Get the Farmer ID linked to this session
$tsql_farmer = "SELECT id FROM farmers WHERE user_id = ?";
$stmt_farmer = sqlsrv_query($conn, $tsql_farmer, array($user_id));
$farmer_data = sqlsrv_fetch_array($stmt_farmer, SQLSRV_FETCH_ASSOC);
$farmer_id = $farmer_data['id'] ?? 0;

// 2. Fetch policies (Using LEFT JOIN so policies show up even if item info is missing)
$tsql_policies = "SELECT p.id, p.policy_type, p.coverage_amount, p.status, p.end_date,
                  c.type as item_name, c.category,
                  DATEDIFF(day, GETDATE(), p.end_date) as days_remaining
                  FROM policies p
                  LEFT JOIN crops_livestock c ON p.item_id = c.id
                  WHERE p.farmer_id = ?";
                  
$stmt = sqlsrv_query($conn, $tsql_policies, array($farmer_id));

$all_policies = []; // This MUST match the name in your foreach loop below
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Ensure item_name isn't null for the display
        $row['item_name'] = $row['item_name'] ?? 'General Crop';
        $row['category'] = $row['category'] ?? 'N/A';
        $all_policies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Policies - Agricultural Insurance</title>
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h1 {
            font-size: 1.5em;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }

        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 0.9em;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .tab {
            padding: 12px 25px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .policy-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .policy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .policy-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .policy-header h3 {
            font-size: 1.4em;
            margin-bottom: 5px;
        }

        .policy-id {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-expired {
            background: #f8d7da;
            color: #721c24;
        }

        .policy-body {
            padding: 25px;
        }

        .policy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .policy-detail {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .policy-detail label {
            display: block;
            color: #666;
            font-size: 0.85em;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .policy-detail value {
            color: #333;
            font-size: 1.1em;
            font-weight: 600;
        }

        .covered-item {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .covered-item strong {
            color: #333;
        }

        .policy-actions {
            display: flex;
            gap: 10px;
            padding: 20px 25px;
            background: #f8f9fa;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .loading {
            text-align: center;
            padding: 60px;
            color: #666;
        }

        .empty-state {
            background: white;
            text-align: center;
            padding: 60px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
        }

        .btn-large {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-block;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -35px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
        }

        .timeline-item strong {
            color: #333;
        }

        .timeline-item small {
            color: #999;
            display: block;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .policy-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Keep your existing CSS here */
        body { font-family: 'Segoe UI', sans-serif; background: #f5f7fa; margin: 0; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .policy-card { background: white; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; }
        .policy-header { background: #764ba2; color: white; padding: 20px; display: flex; justify-content: space-between; }
        .policy-body { padding: 20px; }
        .badge { padding: 5px 15px; border-radius: 20px; font-size: 0.8em; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🌾 Agricultural Insurance</h1>
        <div>
            <a href="dashboard.php" style="color:white; margin-right:20px;">Dashboard</a>
            <span>👤 <?php echo htmlspecialchars($username); ?></span>
        </div>
    </div>

    <div class="container">
        <h2>📋 My Insurance Policies</h2>
        
        <?php if (empty($all_policies)): ?>
            <div style="background: white; padding: 40px; text-align: center; border-radius: 15px;">
                <h3>No policies found in database</h3>
                <p>Your account (Farmer ID: <?php echo $farmer_id; ?>) has no linked insurance records.</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_policies as $policy): ?>
                <div class="policy-card">
                    <div class="policy-header">
                        <div>
                            <h3><?php echo htmlspecialchars($policy['policy_type']); ?></h3>
                            <small>Policy ID: #<?php echo $policy['id']; ?></small>
                        </div>
                        <span class="badge badge-<?php echo $policy['status']; ?>">
                            <?php echo strtoupper($policy['status']); ?>
                        </span>
                    </div>
                    <div class="policy-body">
                        <p><strong>Covered Item:</strong> <?php echo htmlspecialchars($policy['item_name']); ?> (<?php echo $policy['category']; ?>)</p>
                        <p><strong>Coverage:</strong> ₱<?php echo number_format($policy['coverage_amount'], 2); ?></p>
                        <p><strong>Remaining:</strong> <?php echo $policy['days_remaining']; ?> days</p>
                        <hr>
                        <button onclick="window.location.href='file_claim.php?policy=<?php echo $policy['id']; ?>'" 
                                style="background:#667eea; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;">
                            File a Claim
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script>
    // Replace the hardcoded allPolicies array with this PHP injection:
    let allPolicies = <?php echo json_encode($policies); ?>;
    
    // The rest of your filterPolicies() and displayPolicies() functions 
    // will now work with real data from SSMS!
    window.addEventListener('DOMContentLoaded', () => {
        updateStats();
        displayPolicies(allPolicies);
    });
</script>
</body>
</html>










