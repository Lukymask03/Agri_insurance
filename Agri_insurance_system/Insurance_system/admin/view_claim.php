<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

// Security: Only Admin or Adjuster can manage/view details here
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('adjuster'))) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$claim_id = $_GET['id'] ?? null;
$error = '';
$success_msg = '';

if (!$claim_id) {
    header('Location: manage_claims.php');
    exit;
}

/// 1. Handle Status Updates (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    
    // FIX: If rejecting, force payout to 0. Otherwise, use the form value.
    $approved_payout = ($new_status === 'Rejected') ? 0 : ($_POST['approved_payout'] ?? 0);
    
    if (sqlsrv_begin_transaction($conn) === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    try {
        $updateSql = "UPDATE claims SET status = ?, approved_payout = ? WHERE id = ?";
        $params = [$new_status, $approved_payout, $claim_id];
        $stmt = sqlsrv_query($conn, $updateSql, $params);

        // FIX: Manually throw exception because sqlsrv_query doesn't trigger 'catch' automatically
        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_commit($conn);
        $success_msg = "Claim marked as $new_status successfully!";
        
        // Refresh claim data to update the UI immediately
        $stmt = sqlsrv_query($conn, $query, [$claim_id]);
        $claim = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        $error = "Update failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Claim #<?php echo $claim_id; ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .detail-card { background: white; max-width: 600px; margin: auto; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.8em; }
        .pending { background: #fff3cd; color: #856404; }
        .approved { background: #d4edda; color: #155724; }
        .rejected { background: #f8d7da; color: #721c24; }
        .input-group { margin: 20px 0; }
        input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: bold; }
        .btn-approve { background: #28a745; }
        .btn-reject { background: #dc3545; }
    </style>
</head>
<body>

<div class="detail-card">
    <a href="manage_claims.php" style="text-decoration:none; color:#673ab7;">← Back to List</a>
    <h2 style="margin-top:15px;">Claim Details #<?php echo $claim['id']; ?></h2>
    
    <?php if($success_msg): ?> <div style="color:green; margin-bottom:10px;"><?php echo $success_msg; ?></div> <?php endif; ?>

    <div style="background:#f9f9f9; padding:15px; border-radius:8px;">
        <p><strong>Farmer:</strong> <?php echo htmlspecialchars($claim['farmer_name']); ?></p>
        <p><strong>Claim Type:</strong> <?php echo htmlspecialchars($claim['claim_type']); ?></p>
        <p><strong>Estimated Loss:</strong> ₱<?php echo number_format($claim['estimated_loss'], 2); ?></p>
        <p><strong>Policy Coverage:</strong> ₱<?php echo number_format($claim['coverage_amount'], 2); ?></p>
        <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower($claim['status']); ?>"><?php echo $claim['status']; ?></span></p>
    </div>

    <?php if ($claim['status'] === 'Pending'): ?>
    <form method="POST">
        <div class="input-group">
            <label><strong>Final Approved Payout (₱):</strong></label>
            <input type="number" name="approved_payout" value="<?php echo $claim['estimated_loss']; ?>" max="<?php echo $claim['coverage_amount']; ?>" step="0.01" required>
            <small>Must not exceed ₱<?php echo number_format($claim['coverage_amount'], 2); ?></small>
        </div>
        
        <div style="display:flex; gap:10px;">
            <button type="submit" name="new_status" value="Approved" class="btn btn-approve">Approve Claim</button>
            <button type="submit" name="new_status" value="Rejected" class="btn btn-reject" onclick="return confirm('Reject this claim?')">Reject Claim</button>
        </div>
    </form>
    <?php else: ?>
        <p style="margin-top:20px; color:#666; font-style:italic;">This claim has already been processed as <strong><?php echo $claim['status']; ?></strong>.</p>
    <?php endif; ?>
</div>

</body>
</html>