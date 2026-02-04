<?php
session_start();
require_once '../backend/db.php';
$conn = getConn();

// 1. Get Claim Details
$claim_id = $_GET['id'] ?? 0;
$tsql = "SELECT c.*, f.full_name, p.policy_type, p.coverage_amount 
         FROM claims c 
         JOIN policies p ON c.policy_id = p.id
         JOIN farmers f ON p.farmer_id = f.id
         WHERE c.id = ?";
$stmt = sqlsrv_query($conn, $tsql, array($claim_id));
$claim = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// 2. Handle the Approval/Payout Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $approved_amount = $_POST['approved_amount'];
    
    // Update the claim status and record the final amount
    $update_sql = "UPDATE claims SET status = 'Approved', approved_payout = ? WHERE id = ?";
    $update_stmt = sqlsrv_query($conn, $update_sql, array($approved_amount, $claim_id));
    
    if ($update_stmt) {
        header("Location: adjuster_dashboard.php?msg=SettlementSentToAdmin");
        exit;
    }
}
?>

<div class="card">
    <h3>Process Payout for: <?php echo $claim['full_name']; ?></h3>
    <p>Policy Coverage: ₱<?php echo number_format($claim['coverage_amount'], 2); ?></p>
    <p>Farmer's Estimated Loss: ₱<?php echo number_format($claim['estimated_loss'], 2); ?></p>
    
    <form method="POST">
        <label>Final Approved Amount (₱):</label>
        <input type="number" name="approved_amount" max="<?php echo $claim['coverage_amount']; ?>" required>
        <button type="submit">Approve for Settlement</button>
    </form>
</div>