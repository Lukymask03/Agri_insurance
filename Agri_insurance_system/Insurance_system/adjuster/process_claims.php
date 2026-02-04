<?php
session_start();
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php'; 

if (!isLoggedIn() || !hasRole('adjuster')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$username = $_SESSION['username'] ?? 'Adjuster';
$success = '';
$error = '';

// 1. DATABASE UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $claim_id = $_POST['claim_id'];
    $processed_by_id = $_SESSION['user_id'] ?? 1;

    try {
        // Fetch policy limit from SSMS
        $limit_sql = "SELECT p.coverage_amount FROM claims c JOIN insurance_policies p ON c.policy_id = p.id WHERE c.id = ?";
        $limit_stmt = sqlsrv_query($conn, $limit_sql, [$claim_id]);
        $limit_row = sqlsrv_fetch_array($limit_stmt, SQLSRV_FETCH_ASSOC);
        $policy_limit = $limit_row['coverage_amount'];

        if ($action === 'approve') {
            $approved_amount = $_POST['approved_amount'];
            if ($approved_amount > $policy_limit) {
                throw new Exception("Approved amount exceeds the Policy Coverage Limit (₱" . number_format($policy_limit) . ").");
            }

            $sql = "UPDATE claims SET status = 'approved', settlement_amount = ?, processed_by = ?, decision_date = GETDATE() WHERE id = ?";
            $params = [$approved_amount, $processed_by_id, $claim_id];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
            $success = "Claim #$claim_id successfully APPROVED.";

        } elseif ($action === 'reject') {
            $sql = "UPDATE claims SET status = 'rejected', processed_by = ?, decision_date = GETDATE() WHERE id = ?";
            $params = [$processed_by_id, $claim_id];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if($stmt === false) { die(print_r(sqlsrv_errors(), true)); }
            $success = "Claim #$claim_id has been REJECTED.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 2. FETCH DATA & RISK CHECK
$claim_id_url = $_GET['claim_id'] ?? $_POST['claim_id'] ?? null;
if ($claim_id_url) {
    $sql = "SELECT c.*, f.full_name as farmer_name, f.id as farmer_id, p.policy_type, p.coverage_amount
            FROM claims c 
            JOIN insurance_policies p ON c.policy_id = p.id 
            JOIN crops_livestock cl ON p.item_id = cl.id
            JOIN farmers f ON cl.farmer_id = f.id
            WHERE c.id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$claim_id_url]);
    $claim = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$claim) { die("Claim not found."); }

    // Check for high-frequency claims (Risk Assessment)
    $risk_sql = "SELECT COUNT(*) as total FROM claims c 
                 JOIN insurance_policies p ON c.policy_id = p.id 
                 JOIN crops_livestock cl ON p.item_id = cl.id 
                 WHERE cl.farmer_id = ? AND c.status = 'approved'";
    $risk_stmt = sqlsrv_query($conn, $risk_sql, [$claim['farmer_id']]);
    $risk_data = sqlsrv_fetch_array($risk_stmt, SQLSRV_FETCH_ASSOC);
    $prev_claims = $risk_data['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Claim #<?php echo $claim['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --emerald: #059669; --bg: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: #1e293b; }
        .header { background: white; padding: 1rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .card { background: white; border-radius: 12px; border: 1px solid var(--border); padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .badge { padding: 0.5rem 1rem; border-radius: 99px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .label { font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
        .value { font-size: 1.1rem; font-weight: 600; color: #0f172a; margin-bottom: 1.5rem; display: block; }
        .btn { width: 100%; padding: 0.8rem; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; margin-bottom: 1rem; transition: 0.2s; }
        .btn-approve { background: var(--emerald); color: white; }
        .btn-approve:hover { background: #047857; }
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #fecaca; }
        .modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; width: 400px; margin: 10% auto; padding: 2rem; border-radius: 12px; }
        input[type="number"] { width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 6px; margin-bottom: 1rem; }
        .risk-alert { background: #fff1f2; color: #9f1239; border: 1px solid #fda4af; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<header class="header">
    <div style="font-weight:700; color:var(--emerald);">🛡️ ADJUSTER REVIEW</div>
    <a href="manage_claims.php" style="color:#64748b; text-decoration:none; font-size:0.9rem;">← Back to List</a>
</header>

<div class="container">
    <?php if ($success): ?> <div style="background:#dcfce7; color:#166534; padding:1rem; border-radius:8px; margin-bottom:1rem;">✅ <?php echo $success; ?></div> <?php endif; ?>
    <?php if ($error): ?> <div style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">❌ <?php echo $error; ?></div> <?php endif; ?>

    <?php if ($prev_claims > 2): ?>
        <div class="risk-alert">
            <strong>⚠️ High Risk Claimant:</strong> This farmer has <strong><?php echo $prev_claims; ?></strong> approved claims in the system.
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h2 style="margin:0;">Claim #<?php echo $claim['id']; ?></h2>
                <span class="badge badge-pending"><?php echo $claim['status']; ?></span>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr;">
                <div>
                    <span class="label">Farmer</span>
                    <span class="value"><?php echo htmlspecialchars($claim['farmer_name']); ?></span>
                </div>
                <div>
                    <span class="label">Policy Type</span>
                    <span class="value"><?php echo htmlspecialchars($claim['policy_type']); ?></span>
                </div>
                <div>
                    <span class="label">Requested Amount</span>
                    <span class="value" style="color:var(--emerald);">₱<?php echo number_format($claim['settlement_amount'], 2); ?></span>
                </div>
                <div>
                    <span class="label">Max Coverage</span>
                    <span class="value" style="color:#2563eb;">₱<?php echo number_format($claim['coverage_amount'], 2); ?></span>
                </div>
            </div>

            <span class="label">Description</span>
            <div style="background:#f1f5f9; padding:1rem; border-radius:8px; font-size:0.95rem; line-height:1.6;">
                <?php echo nl2br(htmlspecialchars($claim['claim_description'])); ?>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Actions</h3>
            <p style="font-size:0.85rem; color:#64748b; margin-bottom:1.5rem;">Process this claim after verifying the description and evidence.</p>
            
            <?php if ($claim['status'] == 'pending'): ?>
                <button class="btn btn-approve" onclick="document.getElementById('approveModal').style.display='block'">Approve Claim</button>
                <button class="btn btn-reject" onclick="document.getElementById('rejectModal').style.display='block'">Reject Claim</button>
            <?php else: ?>
                <div style="text-align:center; padding:1rem; color:#64748b;">Processed on <?php echo $claim['decision_date']->format('Y-m-d'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="approveModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Finalize Approval</h3>
        <form method="POST">
            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
            <input type="hidden" name="action" value="approve">
            <label class="label">Approved Settlement (₱)</label>
            <input type="number" name="approved_amount" value="<?php echo $claim['settlement_amount']; ?>" max="<?php echo $claim['coverage_amount']; ?>" required>
            <button type="submit" class="btn btn-approve">Submit Approval</button>
            <button type="button" class="btn" style="background:none; color:#64748b;" onclick="this.closest('.modal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Confirm Rejection</h3>
        <form method="POST">
            <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
            <input type="hidden" name="action" value="reject">
            <p>Are you sure you want to reject this claim?</p>
            <button type="submit" class="btn btn-reject">Yes, Reject Claim</button>
            <button type="button" class="btn" style="background:none; color:#64748b;" onclick="this.closest('.modal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

</body>
</html>