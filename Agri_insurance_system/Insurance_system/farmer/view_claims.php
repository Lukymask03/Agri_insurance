<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

// FIX 1: Added 'farmer' to allowed roles so you aren't redirected
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('adjuster') && !hasRole('agent') && !hasRole('farmer'))) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$user_id = $_SESSION['user_id']; // Get the logged-in farmer's ID
$error = '';
$success_msg = '';

// FIX 2: Changed query to fetch ALL claims for this specific farmer
// We join with 'farmers' to ensure we match the user_id from the session
$query = "SELECT c.*, p.policy_type 
          FROM claims c
          JOIN insurance_policies p ON c.policy_id = p.id
          JOIN farmers f ON p.farmer_id = f.id
          WHERE f.user_id = ? 
          ORDER BY c.id DESC";

$stmt = sqlsrv_query($conn, $query, [$user_id]);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Claim History</title>
    <style>
        :root { --purple: #673ab7; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 1000px; margin: auto; }
        
        .table-container {
            max-height: 600px; 
            overflow-y: auto;
            margin-top: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        table { width: 100%; border-collapse: collapse; }
        
        thead th { 
            position: sticky; 
            top: 0; 
            background: var(--purple); 
            color: white; 
            padding: 15px; 
            text-align: left;
            z-index: 10;
        }

        td { padding: 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 10px; display: inline-block; }
        
        /* Status Colors */
        .pending { background: #fff3cd; color: #856404; }
        .approved { background: #cce5ff; color: #004085; }
        .rejected { background: #f8d7da; color: #721c24; }
        .paid { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>📋 My Claims History</h2>
                <p style="color:#666; font-size: 0.9em;">Logged in as: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <a href="dashboard.php" style="text-decoration:none; color:var(--purple); font-weight:bold;">← Back to Dashboard</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Policy Type</th>
                        <th>Claim Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 0;
                    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                        $count++;
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['policy_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['claim_type']); ?></td>
                        <td>
                            <?php $s = strtolower($row['status'] ?? 'pending'); ?>
                            <span class="badge <?php echo $s; ?>"><?php echo strtoupper($s); ?></span>
                        </td>
                        <td>
                            <a href="receipt.php?id=<?php echo $row['id']; ?>" style="color:var(--purple); font-weight:bold;">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if($count == 0): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:30px; color:#999;">No claims found in your history.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>