<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$policy_id = $_GET['id'] ?? 0;

// Handle Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $sql = "UPDATE insurance_policies SET status = ? WHERE id = ?";
    $params = array($new_status, $policy_id);
    $update_stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($update_stmt) {
        header("Location: manage_policies.php?msg=updated");
        exit;
    }
}

// Get current data
$tsql = "SELECT * FROM insurance_policies WHERE id = ?";
$stmt = sqlsrv_query($conn, $tsql, array($policy_id));
$policy = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Policy</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 50px; }
        .form-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 400px; }
        select, input { width: 100%; padding: 10px; margin: 10px 0 20px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #1e3a8a; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit Policy Status</h2>
        <form method="POST">
            <label>Policy ID</label>
            <input type="text" value="<?php echo $policy['id']; ?>" disabled>
            
            <label>Current Status</label>
            <select name="status">
                <option value="Active" <?php echo ($policy['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                <option value="Pending" <?php echo ($policy['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="Expired" <?php echo ($policy['status'] == 'Expired') ? 'selected' : ''; ?>>Expired</option>
            </select>
            
            <button type="submit">Update Policy</button>
        </form>
        <br>
        <a href="manage_policies.php">Cancel</a>
    </div>
</body>
</html>