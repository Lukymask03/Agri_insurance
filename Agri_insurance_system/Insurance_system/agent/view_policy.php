<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../backend/db.php';
require_once '../backend/auth.php';

// 1. Security check for AGENT role
if (!isLoggedIn() || !hasRole('agent')) {
    header("Location: ../views/login.php");
    exit();
}

$conn = getConn();
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Insurance Agent';

// 2. Get the Policy ID from the URL (dashboard.php?id=...)
$policy_id = isset($_GET['id']) ? $_GET['id'] : 0;

// 3. Fetch Policy Data using your specific View
$tsql = "SELECT * FROM view_active_policies WHERE policy_id = ?";
$params = array($policy_id);
$stmt = sqlsrv_query($conn, $tsql, $params);

if ($stmt === false || !($policy = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    die("Policy #$policy_id not found or Database Error.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Policy Details - #<?php echo $policy_id; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 40px; }
        .details-card { background: white; padding: 30px; border-radius: 12px; max-width: 800px; margin: auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 25px; color: #1e3a8a; display: flex; justify-content: space-between; align-items: center;}
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { margin-bottom: 15px; }
        .label { font-weight: bold; color: #666; font-size: 0.85rem; text-transform: uppercase; display: block; }
        .value { font-size: 1.1rem; color: #333; margin-top: 5px; display: block; }
        .status-badge { background: #d1fae5; color: #065f46; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.8rem; }
        .back-btn { display: inline-block; margin-top: 25px; color: #1e3a8a; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="details-card">
        <div class="header">
            <h2>Policy Information</h2>
            <span class="status-badge">ACTIVE</span>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Policy ID</span>
                <span class="value">#<?php echo htmlspecialchars($policy['policy_id']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Farmer Name</span>
                <span class="value"><?php echo htmlspecialchars($policy['farmer_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Policy Type</span>
                <span class="value"><?php echo htmlspecialchars($policy['policy_type']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Premium / Coverage</span>
                <span class="value" style="color: #1e3a8a; font-weight: bold;">
                    ₱<?php echo number_format($policy['premium'] ?? $policy['coverage_amount'], 2); ?>
                </span>
            </div>
            <div class="info-item">
                <span class="label">Start Date</span>
                <span class="value"><?php echo $policy['start_date'] ? $policy['start_date']->format('M d, Y') : 'N/A'; ?></span>
            </div>
            <div class="info-item">
                <span class="label">End Date</span>
                <span class="value"><?php echo $policy['end_date'] ? $policy['end_date']->format('M d, Y') : 'N/A'; ?></span>
            </div>
        </div>

        <a href="dashboard.php" class="back-btn">← Back to Agent Dashboard</a>
    </div>
</body>
</html>