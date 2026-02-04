<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

if (!isLoggedIn() || !hasRole('farmer')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Farmer';
$user_id = $_SESSION['user_id'] ?? 0;

// 1. Fetch the Farmer ID linked to this User Account
$farmer_id = 0;
$tsql_farmer = "SELECT id FROM farmers WHERE user_id = ?";
$stmt_farmer = sqlsrv_query($conn, $tsql_farmer, array($user_id));

if ($stmt_farmer && $farmer_data = sqlsrv_fetch_array($stmt_farmer, SQLSRV_FETCH_ASSOC)) {
    $farmer_id = $farmer_data['id'];
}

// 2. Fetch Policies 
// We use a broader query first to ensure we get results, 
// joining insurance_policies with crops_livestock to get the item names.
$tsql_policies = "SELECT 
                    p.id AS policy_id, 
                    p.policy_type, 
                    c.type AS item_type,
                    p.status
                  FROM insurance_policies p
                  JOIN crops_livestock c ON p.item_id = c.id
                  WHERE p.farmer_id = ? AND (p.status = 'active' OR p.status = 'pending')";

$stmt_policies = sqlsrv_query($conn, $tsql_policies, array($farmer_id));

$policies = [];
if ($stmt_policies) {
    while ($row = sqlsrv_fetch_array($stmt_policies, SQLSRV_FETCH_ASSOC)) {
        $policies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Claim - Agricultural Insurance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; }
        .navbar { background: #9061f9; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .header-card { background: white; padding: 30px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header-card h1 { color: #5521b5; margin: 0; font-size: 28px; }
        .back-link { text-decoration: none; color: #7e3af2; font-weight: bold; display: inline-block; margin-bottom: 15px; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .section-title { color: #5521b5; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 25px; font-size: 20px; }
        .form-group { margin-bottom: 25px; }
        label { display: block; font-weight: 600; margin-bottom: 10px; color: #4b5563; }
        select, textarea, input[type="file"] { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        .submit-btn { background: #7e3af2; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 18px; transition: 0.3s; }
        .submit-btn:hover { background: #6c2bd9; }
        .no-data-alert { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="navbar">
        <div style="font-weight: bold; font-size: 20px;">🌾 Agricultural Insurance System</div>
        <div>👤 <?php echo htmlspecialchars($display_name); ?> (Farmer)</div>
    </div>

    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header-card">
            <h1>📝 File Insurance Claim</h1>
            <p>Submit a claim for crop damage or livestock loss covered by your active policies.</p>
        </div>

        <div class="form-card">
            <?php if (empty($policies)): ?>
                <div class="no-data-alert">
                    <strong>Notice:</strong> No active insurance policies were found for your account. 
                    You must have an approved policy before you can file a claim.
                </div>
            <?php endif; ?>

            <form action="../backend/submit_claim.php" method="POST" enctype="multipart/form-data">    

                <div class="section-title">1. Select Policy</div>
                <div class="form-group">
                    <label>Choose Active Policy *</label>
                    <select name="policy_id" required <?php echo empty($policies) ? 'disabled' : ''; ?>>
                        <option value="">-- <?php echo empty($policies) ? 'No policies available' : 'Select a policy'; ?> --</option>
                        <?php foreach ($policies as $p): ?>
                            <option value="<?php echo $p['policy_id']; ?>">
                                <?php echo htmlspecialchars($p['policy_type'] . " - " . $p['item_type'] . " [" . ucfirst($p['status']) . "]"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="section-title">2. Claim Details</div>
                <div class="form-group">
                    <label>Type of Claim *</label>
                    <select name="claim_type" required>
                        <option value="Natural Disaster">Natural Disaster</option>
                        <option value="Pest/Disease">Disease/Pest</option>
                        <option value="Accident">Accident</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Detailed Description *</label>
                    <textarea name="description" rows="5" placeholder="Please describe the extent of damage..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Upload Evidence (Photos) *</label>
                    <input type="file" name="evidence" accept="image/*" required>
                </div>

                <button type="submit" class="submit-btn" <?php echo empty($policies) ? 'disabled style="background: #ccc; cursor: not-allowed;"' : ''; ?>>
                    Submit Claim Application
                </button>
            </form>
        </div>
    </div>
</body>
</html>