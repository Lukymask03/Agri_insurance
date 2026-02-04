<?php
session_start();
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/db.php';

if (!isLoggedIn() || !hasRole('adjuster')) {
    header('Location: ../views/login.php');
    exit();
}

$conn = getConn(); 

// FIXED: Uses 'claim_date' and 'estimated_loss' to match your SSMS schema
// FIXED: Uses the Policy ID placeholder to avoid Farmer table column errors
$query = "SELECT 
            c.id, 
            c.claim_date, 
            'Policy #' + CAST(c.policy_id AS VARCHAR) as farmer_name, 
            p.policy_type as policy_name,
            c.estimated_loss, 
            c.status
          FROM claims c
          JOIN insurance_policies p ON c.policy_id = p.id
          ORDER BY c.claim_date DESC"; 

$res = sqlsrv_query($conn, $query);

if ($res === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Claims - Adjuster Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { --emerald: #059669; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: #1e293b; }
        .header { background: white; padding: 1rem 2rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 1rem; text-align: left; font-size: 0.75rem; color: #64748b; text-transform: uppercase; }
        td { padding: 1.25rem 1rem; border-top: 1px solid #f1f5f9; font-size: 0.9rem; }
        .badge { padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-processing { background: #e0f2fe; color: #075985; }
        .btn-review { color: var(--emerald); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <header class="header">
        <div style="font-weight:700; color:var(--emerald); font-size: 1.2rem;">🛡️ ADJUSTER PORTAL</div>
        <a href="dashboard.php" style="text-decoration:none; color:#64748b; font-size: 0.9rem;">← Back to Dashboard</a>
    </header>
    <div class="container">
        <h2 style="margin-bottom: 1.5rem;">Insurance <span style="color:var(--emerald);">Claims</span> Management</h2>
        <div class="card">
            <table>
                <thead>
                    <tr><th>Date</th><th>Farmer</th><th>Policy</th><th>Amount</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while($claim = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $claim['claim_date'] ? $claim['claim_date']->format('Y-m-d') : 'N/A'; ?></td>
                        <td><strong><?php echo htmlspecialchars($claim['farmer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($claim['policy_name']); ?></td>
                        <td>₱<?php echo number_format($claim['estimated_loss'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($claim['status']); ?>">
                                <?php echo ucfirst($claim['status']); ?>
                            </span>
                        </td>
                        <td><a href="process_claims.php?claim_id=<?php echo $claim['id']; ?>" class="btn-review">Process Claim →</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>