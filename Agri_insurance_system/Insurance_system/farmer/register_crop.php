<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn();
$success_msg = "";
$error_msg = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $farmer_id = $_POST['farmer_id'];
    $category = $_POST['category'];
    $type = $_POST['type'];
    $quantity = $_POST['quantity'];
    $location = $_POST['location'];

    // Insert into crops_livestock table
    $tsql = "INSERT INTO crops_livestock (farmer_id, category, type, quantity, location) VALUES (?, ?, ?, ?, ?)";
    $params = array($farmer_id, $category, $type, $quantity, $location);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        $success_msg = "Item registered successfully! You can now create a policy for it.";
    } else {
        $error_msg = "Registration failed: " . print_r(sqlsrv_errors(), true);
    }
}

// Fetch all farmers for the dropdown
$farmers_stmt = sqlsrv_query($conn, "SELECT id, full_name FROM farmers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Item - Agricultural Insurance</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 40px; }
        .form-container { max-width: 500px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #667eea; color: white; padding: 12px; border: none; width: 100%; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #764ba2; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>🌾 Register Crop/Livestock</h2>
    <p>Add an item before creating an insurance policy.</p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Select Farmer</label>
            <select name="farmer_id" required>
                <option value="">-- Choose Farmer --</option>
                <?php while($f = sqlsrv_fetch_array($farmers_stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <option value="<?php echo $f['id']; ?>"><?php echo $f['full_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category" required>
                <option value="Crop">Crop</option>
                <option value="Livestock">Livestock</option>
            </select>
        </div>

        <div class="form-group">
            <label>Type (e.g. Rice, Corn, Cattle)</label>
            <input type="text" name="type" placeholder="Enter type" required>
        </div>

        <div class="form-group">
            <label>Quantity / Area (Hectares or Head count)</label>
            <input type="number" name="quantity" required>
        </div>

        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" placeholder="e.g. Sector 7, Nueva Ecija" required>
        </div>

        <button type="submit" class="btn">Register Item</button>
        <a href="dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#666; text-decoration:none;">Back to Dashboard</a>
    </form>
</div>

</body>
</html>