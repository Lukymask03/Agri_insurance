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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['full_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone_number'];

    $sql = "INSERT INTO farmers (full_name, address, phone_number) VALUES (?, ?, ?)";
    $params = array($name, $address, $phone);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header('Location: manage_farmers.php?success=1');
        exit;
    } else {
        $error = "Error: " . print_r(sqlsrv_errors(), true);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Farmer</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding-top: 50px; }
        .form-card { background: white; padding: 30px; border-radius: 12px; width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        h2 { color: #8e44ad; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #8e44ad; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; margin-top: 20px; transition: 0.3s; }
        .btn:hover { background: #732d91; }
        .cancel-link { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>New Farmer Registration</h2>
        <?php if($error): ?> <p style="color:red; font-size: 0.8rem;"><?php echo $error; ?></p> <?php endif; ?>
        
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="e.g. Juan Dela Cruz">
            
            <label>Address</label>
            <input type="text" name="address" required placeholder="e.g. Cagayan De Oro City">
            
            <label>Phone Number</label>
            <input type="text" name="phone_number" required placeholder="e.g. 09123456789">
            
            <button type="submit" class="btn">Save Farmer</button>
            <a href="manage_farmers.php" class="cancel-link">Cancel and Go Back</a>
        </form>
    </div>
</body>
</html>