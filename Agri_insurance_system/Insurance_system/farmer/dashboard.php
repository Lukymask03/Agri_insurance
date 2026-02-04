<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

$conn = getConn();
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Farmer'; 

// 1. FETCH FARMER PROFILE
$tsql = "SELECT id, full_name, phone_number AS contact_number, email 
         FROM dbo.farmers 
         WHERE user_id = ?"; 

$stmt = sqlsrv_query($conn, $tsql, array($user_id));
$farmer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$farmer_id = $farmer['id'] ?? null;

// 2. FETCH POLICIES
$my_policies = [];
if ($farmer_id) {
    $tsql_p = "SELECT id, policy_type, coverage_amount, status FROM insurance_policies WHERE farmer_id = ?";
    $stmt_p = sqlsrv_query($conn, $tsql_p, array($farmer_id));
    while ($row = sqlsrv_fetch_array($stmt_p, SQLSRV_FETCH_ASSOC)) {
        $my_policies[] = $row;
    }
}

// 3. FETCH RECENT CLAIMS
$my_claims = [];
if ($farmer_id) {
    $tsql_c = "SELECT TOP 5 id, claim_type, estimated_loss AS amount_claimed, status FROM claims 
               WHERE policy_id IN (SELECT id FROM insurance_policies WHERE farmer_id = ?)
               ORDER BY id DESC";
    $stmt_c = sqlsrv_query($conn, $tsql_c, array($farmer_id));
    while ($row = sqlsrv_fetch_array($stmt_c, SQLSRV_FETCH_ASSOC)) {
        $my_claims[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --purple: #673ab7; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }
        .sidebar { width: 260px; background: var(--purple); color: white; height: 100vh; position: fixed; padding: 20px; }
        .main-content { margin-left: 260px; flex: 1; padding: 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .item { border-bottom: 1px solid #eee; padding: 12px 0; display: flex; justify-content: space-between; align-items: center; }
        .item:last-child { border-bottom: none; }
        .nav-item { padding: 12px; display: block; color: white; text-decoration: none; border-radius: 8px; margin-bottom: 5px; }
        .nav-item:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🌾 Agri-Insurance</h2>
        <hr style="opacity:0.3">
        <nav>
    <a href="dashboard.php" class="nav-item">Dashboard</a>
    <a href="my_crops.php" class="nav-item">My Crops/Livestock</a>
    <a href="my_policies.php" class="nav-item">My Policies</a>
    <a href="./view_claims.php" class="nav-item">View Claims History</a>
    
    <a href="javascript:void(0)" class="nav-item" onclick="logout()" style="background:rgba(255,0,0,0.1); margin-top:10px;">
        <span>Logout</span>
    </a>
</nav>

<script>
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            // This triggers the if(isset($_GET['action'])) block in auth.php
            window.location.href = '../backend/auth.php?action=logout';
        }
    }
</script>
    </div>

    <div class="main-content">
        <h1>Welcome, <?php echo htmlspecialchars($farmer['full_name'] ?? $username); ?>!</h1>
        
        <div class="stats-grid">
            <div class="card">
                <h3>My Policies</h3>
                <?php if (empty($my_policies)): ?>
                    <p style="color:#999;">No active policies.</p>
                <?php else: ?>
                    <?php foreach ($my_policies as $p): ?>
                    <div class="item">
                        <span><strong><?php echo $p['policy_type']; ?></strong></span>
                        <span class="badge" style="background:#e8f5e9; color:#2e7d32;"><?php echo $p['status']; ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0;">Recent Claims</h3>
                    <a href="view_claims.php" style="color:var(--purple); font-size:13px; font-weight:bold; text-decoration:none;">View All →</a>
                </div>
                
                <?php if (empty($my_claims)): ?>
                    <div style="color:#999; text-align:center; padding:20px;">No claims filed yet.</div>
                <?php else: ?>
                    <?php foreach ($my_claims as $c): ?>
                    <div class="item">
                        <div>
                            <strong><?php echo htmlspecialchars($c['claim_type']); ?></strong><br>
                            <small>₱<?php echo number_format($c['amount'], 2); ?></small>
                        </div>
                        <div style="text-align:right;">
                            <?php 
                                $s = strtolower($c['status']);
                                $style = "background:#fff3cd; color:#856404;"; // Pending
                                if($s == 'paid') $style = "background:#d4edda; color:#155724;"; // Paid
                                if($s == 'approved') $style = "background:#cce5ff; color:#004085;"; // Approved
                            ?>
                            <span class="badge" style="<?php echo $style; ?>"><?php echo strtoupper($c['status']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../backend/auth.php?action=logout';
            }
        }
    </script>
</body>
</html>