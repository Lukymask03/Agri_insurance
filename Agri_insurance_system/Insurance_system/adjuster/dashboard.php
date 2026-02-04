<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; 

if (!isLoggedIn() || !hasRole('adjuster')) {
    header('Location: ../views/login.php');
    exit();
}
$conn = getConn();
$username = $_SESSION['username'];

// Fetch live counts from SSMS
$tsql_stats = "SELECT 
    (SELECT COUNT(*) FROM claims) as total,
    (SELECT COUNT(*) FROM claims WHERE status = 'pending') as pending,
    (SELECT COUNT(*) FROM claims WHERE status = 'approved') as approved,
    (SELECT ISNULL(SUM(approved_payout), 0) FROM claims WHERE status = 'Paid') as total_payout";

$stmt_stats = sqlsrv_query($conn, $tsql_stats);
$stats = sqlsrv_fetch_array($stmt_stats, SQLSRV_FETCH_ASSOC);

// UPDATED QUERY: Order by date DESC so NEW claims appear first
$recent_claims_query = "SELECT TOP 50
                            c.id, 
                            f.full_name as farmer_name, 
                            p.policy_type, 
                            c.estimated_loss as amount, 
                            c.status 
                        FROM claims c
                        JOIN insurance_policies p ON c.policy_id = p.id
                        JOIN farmers f ON p.farmer_id = f.id
                        ORDER BY c.claim_date DESC"; // <--- THE FIX
$res_recent = sqlsrv_query($conn, $recent_claims_query);

if ($res_recent === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjuster Dashboard - Agri-Insurance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary-emerald: #059669; --bg: #f9fafb; --red: #ef4444; --purple: #673ab7; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; color: #1f2937; }
        .header { background: white; padding: 1rem 2rem; display: flex; justify-content: space-between; border-bottom: 1px solid #e5e7eb; align-items: center; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin: 2rem 0; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .stat-label { color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary-emerald); display: block; margin-top: 0.5rem; }
        .table-card { background: white; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f9fafb; padding: 1rem; text-align: left; font-size: 0.75rem; color: #6b7280; text-transform: uppercase; }
        td { padding: 1rem; border-top: 1px solid #f3f4f6; font-size: 0.9rem; }
        
        .btn-approve { 
            background: var(--purple); 
            color: white; 
            text-decoration: none; 
            padding: 6px 12px; 
            border-radius: 6px; 
            font-size: 0.8rem; 
            font-weight: 600; 
        }
        .btn-approve:hover { background: #5e35b1; }
        
        .status-badge { padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-paid { background: #dbeafe; color: #1e40af; }
        .logout-link { color: var(--red); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <header class="header">
        <div style="font-weight:700; color:var(--primary-emerald); font-size: 1.2rem;">🛡️ Adjuster Portal</div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
            <a href="javascript:void(0)" class="logout-link" onclick="confirmAdjusterLogout()">
                <span>Logout</span>
            </a>
        </div>
    </header>

    <div class="container">
        <h1>Dashboard Overview 👋</h1>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Total Claims</span>
                <span class="stat-value"><?php echo $stats['total']; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Pending Review</span>
                <span class="stat-value"><?php echo $stats['pending']; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Approved</span>
                <span class="stat-value"><?php echo $stats['approved']; ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Total Payout</span>
                <span class="stat-value">₱<?php echo number_format($stats['total_payout'], 2); ?></span>
            </div>
        </div>

        <div class="table-card">
            <div style="padding:1.25rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0">Recent Claims Queue</h3>
                <a href="manage_claims.php" style="color:var(--primary-emerald); text-decoration:none; font-weight:600;">View All Claims →</a>
            </div>
            <table>
                <thead>
                    <tr><th>ID</th><th>Farmer Name</th><th>Policy</th><th>Est. Loss</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php while($row = sqlsrv_fetch_array($res_recent, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['farmer_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['policy_type']); ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if(strtolower($row['status']) == 'pending'): ?>
                                <a href="approve_amount.php?id=<?php echo $row['id']; ?>" class="btn-approve">Review & Approve</a>
                            <?php else: ?>
                                <span style="color:#999; font-style:italic;">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function confirmAdjusterLogout() {
        if (confirm("Sign out of the Adjuster session?")) {
            window.location.href = '../backend/auth.php?action=logout';
        }
    }
    </script>

</body>
</html>