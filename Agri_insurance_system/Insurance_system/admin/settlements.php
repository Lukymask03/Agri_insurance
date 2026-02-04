<?php
session_start();
require_once '../backend/db.php';
$conn = getConn();

// This is the logic that fills your "Box"
$tsql = "SELECT c.id, f.full_name, c.approved_payout, c.status 
         FROM claims c
         JOIN insurance_policies p ON c.policy_id = p.id
         JOIN farmers f ON p.farmer_id = f.id
         WHERE c.status = 'Approved' 
         AND c.approved_payout > 0 
         ORDER BY c.id DESC";

$stmt = sqlsrv_query($conn, $tsql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Disbursements - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
    <style>
        :root { --purple: #673ab7; --emerald: #4caf50; --bg: #f4f7f6; }
        
        body { background-color: var(--bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        .settlement-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin-top: 20px; }
        
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th { background: var(--purple); color: white; padding: 15px; text-align: left; font-size: 0.9rem; text-transform: uppercase; }
        .table-custom td { padding: 15px; border-bottom: 1px solid #eee; color: #444; }
        
        .header { background: var(--purple); color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .nav-link { color: white; text-decoration: none; margin-left: 20px; font-weight: 500; opacity: 0.9; }
        .nav-link:hover { opacity: 1; text-decoration: underline; }
        
        .btn-release { background: var(--emerald); color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.9rem; display: inline-block; transition: background 0.3s; border: none; cursor: pointer; }
        .btn-release:hover { background: #388e3c; }

        .alert-success { 
            background-color: #d4edda; 
            color: #155724; 
            padding: 15px; 
            margin: 20px auto 0; 
            max-width: 1100px; 
            border-radius: 8px; 
            border: 1px solid #c3e6cb; 
            text-align: center; 
            font-weight: bold; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="header">
        <h2 style="margin: 0;">🌾 Agricultural Insurance</h2>
        <div style="display: flex; align-items: center;">
            <a href="dashboard.php" class="nav-link">Dashboard</a> 
            <span style="margin: 0 15px; opacity: 0.5;">|</span>
            <span style="font-weight: bold;">Admin Portal</span>
            <a href="../logout.php" class="nav-link" style="margin-left: 25px;">Logout</a>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'disbursed'): ?>
        <div class="alert-success">
            ✅ Success: Funds have been released and the Farmer's claim is now marked as PAID!
        </div>
    <?php endif; ?>

    <div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
        <div class="settlement-card">
            <div style="border-left: 5px solid var(--purple); padding-left: 15px; margin-bottom: 25px;">
                <h2 style="color: #333; margin: 0;">💰 Pending Disbursements</h2>
                <p style="color: #666; margin: 5px 0 0 0;">Authorized settlements from the Adjuster awaiting final fund release.</p>
            </div>
            
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Claim ID</th>
                        <th>Farmer Name</th>
                        <th>Approved Amount</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $has_records = false;
                    if ($stmt): 
                        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                            $has_records = true;
                    ?>
                    <tr>
                        <td><strong>#<?php echo $row['id']; ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                        <td style="color: #2e7d32; font-weight: bold; font-size: 1.2em;">
                            ₱<?php echo number_format($row['approved_payout'], 2); ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="disburse.php?id=<?php echo $row['id']; ?>" class="btn-release" onclick="return confirm('Confirm disbursement of ₱<?php echo number_format($row['approved_payout'], 2); ?> for this claim?')">
                                Release Funds
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    endif; 
                    
                    if (!$has_records): 
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 60px; color: #999;">
                            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="60" style="opacity: 0.2; display: block; margin: 0 auto 15px;">
                            <p style="font-size: 1.1rem; margin: 0;">Queue is currently empty.</p>
                            <small>Claims will appear here once the Adjuster approves a payout amount.</small>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>