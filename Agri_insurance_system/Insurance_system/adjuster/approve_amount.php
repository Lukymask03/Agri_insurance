<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('adjuster'))) {
    die("Access Denied.");
}

$conn = getConn();
$claim_id = $_GET['id'] ?? 0;

$tsql = "SELECT c.*, p.coverage_amount, f.full_name 
         FROM claims c 
         JOIN insurance_policies p ON c.policy_id = p.id 
         JOIN farmers f ON p.farmer_id = f.id 
         WHERE c.id = ?";

$stmt = sqlsrv_query($conn, $tsql, array($claim_id));
$claim = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$claim) { die("Claim #$claim_id not found."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payout = $_POST['approved_payout'];
    
    // CRITICAL FIX: Update status to 'Approved' and save the payout amount
    $update_sql = "UPDATE claims SET approved_payout = ?, status = 'Approved' WHERE id = ?";
    $params = array($payout, $claim_id);
    
    if (sqlsrv_query($conn, $update_sql, $params)) {
        // Redirect to Settlements to see the claim in the payout queue
        header("Location: ../admin/settlements.php?msg=Approved&id=$claim_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjuster - Verify Claim</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 40px; }
        .box { background: white; padding: 30px; border-radius: 10px; max-width: 500px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .amount-input { width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; border-radius: 5px; font-size: 1.1em; }
        .btn { background: #7c3aed; color: white; border: none; padding: 15px; width: 100%; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .info { background: #f0ebff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="color:#7c3aed;">Claim Verification</h2>
        <hr>
        <div class="info">
            <p><strong>Claim ID:</strong> #<?php echo $claim['id']; ?></p>
            <p><strong>Farmer:</strong> <?php echo htmlspecialchars($claim['full_name']); ?></p>
            <p><strong>Est. Loss:</strong> ₱<?php echo number_format($claim['estimated_loss'], 2); ?></p>
            <p><strong>Policy Limit:</strong> ₱<?php echo number_format($claim['coverage_amount'], 2); ?></p>
        </div>

        <form method="POST">
            <label>Approved Payout Amount (₱):</label>
            <input type="number" name="approved_payout" class="amount-input" 
                   value="<?php echo $claim['estimated_loss']; ?>" 
                   max="<?php echo $claim['coverage_amount']; ?>" step="0.01" required>
            
            <button type="submit" class="btn">Confirm Approval</button>
        </form>
    </div>
</body>
</html>