<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php'; // Correctly include your SSMS connection

if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();

// FETCH REAL POLICIES from your View (SQLQuery2.sql)
$tsql = "SELECT policy_id, farmer_name, policy_type, coverage_amount, status, start_date FROM view_active_policies";
$stmt = sqlsrv_query($conn, $tsql);

$policies = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $policies[] = $row;
    }
}
?>
<tbody>
    <?php foreach($policies as $policy): ?>
    <tr>
        <td><strong>#<?php echo $policy['policy_id']; ?></strong></td>
        <td><?php echo htmlspecialchars($policy['farmer_name']); ?></td>
        <td><?php echo htmlspecialchars($policy['policy_type']); ?></td>
        <td>₱<?php echo number_format($policy['coverage_amount'], 2); ?></td>
        <td><?php echo $policy['start_date']->format('Y-m-d'); ?></td>
        <td>
            <span class="status status-active">Active</span>
        </td>
        <td>
            <a href="view_policy.php?id=<?php echo $policy['policy_id']; ?>" class="action-link">View</a>
            <a href="edit_policy.php?id=<?php echo $policy['policy_id']; ?>" class="action-link" style="color: #64748b;">Edit</a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Policies - Agri-Insurance</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background: #f5f5f5; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .back-btn { text-decoration: none; color: #1e3a8a; font-weight: bold; }
        .container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
        .status { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .action-link { color: #3b82f6; text-decoration: none; font-size: 0.9rem; margin-right: 10px; }
        .action-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="header">
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        <h1>Manage Insurance Policies</h1>
        <a href="create_policy.php" style="background: #1e3a8a; color: white; padding: 0.5rem 1rem; border-radius: 5px; text-decoration: none;">+ New Policy</a>
    </div>

    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Policy ID</th>
                    <th>Farmer Name</th>
                    <th>Policy Type</th>
                    <th>Premium</th>
                    <th>Date Issued</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($policies as $policy): ?>
                <tr>
                    <td><strong><?php echo $policy['id']; ?></strong></td>
                    <td><?php echo $policy['farmer']; ?></td>
                    <td><?php echo $policy['type']; ?></td>
                    <td>₱<?php echo number_format($policy['premium'], 2); ?></td>
                    <td><?php echo $policy['date']; ?></td>
                    <td>
                        <span class="status <?php echo ($policy['status'] == 'Active') ? 'status-active' : 'status-expired'; ?>">
                            <?php echo $policy['status']; ?>
                        </span>
                    </td>
                   <td>
    <a href="view_policy.php?id=<?php echo $policy['id']; ?>" class="action-link">View</a>
    <a href="edit_policy.php?id=<?php echo $policy['id']; ?>" class="action-link" style="color: #64748b;">Edit</a>
</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>