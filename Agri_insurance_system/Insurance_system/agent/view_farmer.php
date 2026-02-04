<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$farmer_id = $_GET['id'] ?? 0;

// Fetch farmer profile and account info
$tsql = "SELECT f.*, u.username, u.email 
         FROM farmers f 
         JOIN users u ON f.user_id = u.id 
         WHERE f.id = ?";
$stmt = sqlsrv_query($conn, $tsql, array($farmer_id));
$farmer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$farmer) { die("Farmer profile not found."); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farmer Profile - <?php echo htmlspecialchars($farmer['full_name']); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .profile-card { background: white; max-width: 500px; margin: auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .banner { background: #1e3a8a; color: white; padding: 30px; text-align: center; }
        .info-section { padding: 30px; }
        .info-group { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .label { color: #666; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
        .value { color: #111; font-size: 1.1rem; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="profile-card">
        <div class="banner">
            <h2 style="margin:0">👨‍🌾 <?php echo htmlspecialchars($farmer['full_name']); ?></h2>
            <p style="opacity:0.8">Farmer ID: #<?php echo $farmer['id']; ?></p>
        </div>
        <div class="info-section">
            <div class="info-group">
                <div class="label">Contact Number</div>
                <div class="value"><?php echo htmlspecialchars($farmer['contact_number']); ?></div>
            </div>
            <div class="info-group">
                <div class="label">Email Address</div>
                <div class="value"><?php echo htmlspecialchars($farmer['email']); ?></div>
            </div>
            <div class="info-group">
                <div class="label">Residential Address</div>
                <div class="value"><?php echo htmlspecialchars($farmer['address']); ?></div>
            </div>
            <a href="manage_farmers.php" style="color:#1e3a8a; text-decoration:none; font-weight:bold;">← Back to Directory</a>
        </div>
    </div>
</body>
</html>