<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$error = '';
$farmer = null;

// 1. Fetch Farmer Data
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM farmers WHERE id = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    
    $farmer = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$farmer) { die("Farmer not found!"); }
}

// 2. Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['full_name'];
    $address = $_POST['address'];
    // FIXED: Changed 'phone_number' to 'contact_number' to match SSMS
    $phone = $_POST['contact_number']; 

    // FIXED SQL: Now points to the correct SSMS column
    $sql = "UPDATE farmers SET full_name = ?, address = ?, contact_number = ? WHERE id = ?";
    $params = array($name, $address, $phone, $id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header('Location: manage_farmers.php?success=updated');
        exit;
    } else {
        $error = "Update failed: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Farmer Details</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding-top: 50px; }
        .form-card { background: white; padding: 30px; border-radius: 12px; width: 450px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        h2 { color: #8e44ad; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-group { margin-top: 25px; display: flex; gap: 10px; }
        .btn { flex: 1; padding: 12px; border-radius: 6px; cursor: pointer; border: none; font-weight: bold; text-align: center; text-decoration: none; }
        .btn-save { background: #8e44ad; color: white; }
        .btn-cancel { background: #eee; color: #666; }
    </style>
</head>
<body>

<div class="form-card">
    <h2>✏️ Edit Farmer Details</h2>
    <?php if($error): ?> <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; font-size:14px;"><?php echo $error; ?></div> <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $farmer['id']; ?>">

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($farmer['full_name']); ?>" required>

        <label>Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($farmer['address']); ?>" required>

        <label>Phone Number</label>
        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($farmer['contact_number'] ?? ''); ?>" required>

        <div class="btn-group">
            <button type="submit" class="btn btn-save">Update Record</button>
            <a href="manage_farmers.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>