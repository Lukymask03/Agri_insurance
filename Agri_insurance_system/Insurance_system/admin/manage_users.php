<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

// 1. Security Check: Admin access only
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$message = '';

// 2. Handle Delete Action with Safety Check
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $current_user_id = $_SESSION['user_id'] ?? 0;

    // Safety: Prevent deleting yourself
    if ($delete_id == $current_user_id) {
        $message = "❌ Error: You cannot delete your own account while logged in.";
    } else {
        // Only delete if the user is NOT another admin (standard safety)
        $sql_delete = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $params = array($delete_id);
        $stmt_delete = sqlsrv_query($conn, $sql_delete, $params);

        if ($stmt_delete) {
            $message = "✅ User deleted successfully!";
        } else {
            $message = "❌ Error deleting user: " . print_r(sqlsrv_errors(), true);
        }
    }
}

// 3. Fetch Users using SQLSRV
$sql = "SELECT id, username, role, status FROM users ORDER BY role ASC, username ASC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$users = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; }
        .navbar { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { padding: 2rem; max-width: 1100px; margin: 0 auto; }
        .card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        h2 { color: #7c3aed; margin-top: 0; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        th { background: #f9fafb; color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #ef4444; }
        .role-agent { background: #dbeafe; color: #1e40af; }
        
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; transition: 0.3s; font-weight: 500; }
        .btn-delete { background: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2; }
        .btn-delete:hover { background: #e53e3e; color: white; }
        .btn-back { background: white; color: #7c3aed; border: 1px solid #7c3aed; margin-bottom: 1.5rem; display: inline-flex; align-items: center; gap: 8px; }
        .alert { padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 1.5rem; border-left: 5px solid #10b981; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">⚙️ User Management</div>
        <div style="font-size: 0.9rem;">Logged in as: <strong>Admin</strong></div>
    </nav>

    <div class="container">
        <a href="dashboard.php" class="btn btn-back"><span>←</span> Back to Dashboard</a>
        
        <div class="card">
            <h2>System Users</h2>
            
            <?php if($message): ?>
                <div class="alert" style="<?php echo (strpos($message, 'Error') !== false) ? 'background:#fee2e2; color:#991b1b; border-color:#ef4444;' : ''; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 3rem; color: #999;">No users found in database.</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><code style="color: #888;">#<?php echo $user['id']; ?></code></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td>
                                <span class="badge role-<?php echo strtolower($user['role']); ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($user['status'])); ?></td>
                            <td style="text-align: right;">
                                <?php if(strtolower($user['role']) !== 'admin'): ?>
                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Confirm deletion?')">Delete</a>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>