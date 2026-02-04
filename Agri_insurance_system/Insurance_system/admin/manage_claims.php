<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || (!hasRole('admin') && !hasRole('adjuster'))) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$claims = [];
$error = '';

try {
    $query = "SELECT c.*, f.full_name, p.policy_type 
              FROM claims c
              JOIN insurance_policies p ON c.policy_id = p.id
              JOIN crops_livestock cl ON p.item_id = cl.id
              JOIN farmers f ON cl.farmer_id = f.id
              ORDER BY c.id DESC";
    
    $stmt = sqlsrv_query($conn, $query);
    if ($stmt === false) { throw new Exception(print_r(sqlsrv_errors(), true)); }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $claims[] = $row;
    }
} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Claims - Agri Insurance</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; margin: 0; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .table th { text-align: left; padding: 14px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.85rem; text-transform: uppercase; }
        .table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; border: 1px solid; }
        .badge-pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-approved { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
        .badge-rejected { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
        .btn-view { color: #7c3aed; text-decoration: none; font-weight: 600; font-size: 0.9rem; padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; transition: 0.2s; }
        .btn-view:hover { background: #f5f3ff; border-color: #7c3aed; }
        .btn-dashboard { color: white; border: 1px solid rgba(255,255,255,0.6); padding: 8px 18px; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
    </style>
</head>
<body>
<div class="header">
    <div style="display: flex; align-items: center; gap: 12px;"><span>📋</span><h2 style="margin: 0; font-size: 1.4rem;">Claims Management</h2></div>
    <a href="dashboard.php" class="btn-dashboard">← Dashboard</a>
</div>
<div class="container">
    <?php if ($error): ?><div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #ef4444;"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="card">
        <h3 style="margin-top: 0; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">Insurance Claims Requests</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Claim ID</th>
                    <th>Farmer</th>
                    <th>Policy Type</th>
                    <th>Claim Amount</th>
                    <th>Status</th>
                    <th>Date Filed</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($claims)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">No claim requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($claims as $c): ?>
                    <tr>
                        <td style="font-family: monospace; color: #64748b;">#CLM-<?php echo $c['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($c['full_name'] ?? 'Unknown'); ?></strong></td>
                        <td><span style="background: #f1f5fe; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem;"><?php echo htmlspecialchars($c['policy_type'] ?? 'N/A'); ?></span></td>
                        <td><strong>₱<?php echo number_format($c['claim_amount'] ?? 0, 2); ?></strong></td>
                        <td>
                            <?php $status = strtolower($c['status'] ?? 'pending'); ?>
                            <span class="badge badge-<?php echo $status; ?>"><?php echo strtoupper($status); ?></span>
                        </td>
                        <td><?php $date = $c['date_filed'] ?? $c['created_at'] ?? null; echo ($date instanceof DateTime) ? $date->format('M d, Y') : "N/A"; ?></td>
                        <td style="text-align: right;"><a href="view_claim.php?id=<?php echo $c['id']; ?>" class="btn-view">View & Process</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>