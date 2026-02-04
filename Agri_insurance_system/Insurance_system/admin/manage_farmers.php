<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); 
$farmers = [];
$error = '';

// --- INTEGRATED SEARCH LOGIC ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];

if ($search !== '') {
    // Search by Full Name, Address, or Contact Number using LIKE for partial matches
    $sql = "SELECT * FROM farmers 
            WHERE full_name LIKE ? 
            OR address LIKE ? 
            OR contact_number LIKE ?
            ORDER BY id DESC";
    $params = ["%$search%", "%$search%", "%$search%"];
} else {
    // Default view: Show all farmers
    $sql = "SELECT * FROM farmers ORDER BY id DESC";
}

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $error = print_r(sqlsrv_errors(), true);
} else {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $farmers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Farmers - Agri Insurance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; }
        .header { background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%); color: white; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 2rem; max-width: 1200px; margin: auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .table th { text-align: left; padding: 14px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.85rem; }
        .table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; }
        .btn-primary { background: #7c3aed; color: white; }
        .btn-outline { border: 1px solid white; color: white; background: transparent; }
        .btn-delete { color: #ef4444; background: #fef2f2; padding: 5px 10px; border-radius: 4px; text-decoration: none; }
        
        /* Search Box Styling */
        .search-container { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-input { padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 8px; width: 300px; outline: none; }
        .search-input:focus { border-color: #7c3aed; }
    </style>
</head>
<body>

<div class="header">
    <h2 style="margin:0;">👨‍🌾 Farmer Management</h2>
    <a href="dashboard.php" class="btn btn-outline">← Dashboard</a>
</div>

<div class="container">
    <?php if($error): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>
                <?php echo $search ? "Search Results" : "Registered Farmers"; ?> 
                (Total: <?php echo count($farmers); ?>)
            </h3>
            <a href="add_farmer.php" class="btn btn-primary">+ Register New Farmer</a>
        </div>

        <form method="GET" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search name, address, or phone..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if($search): ?>
                <a href="manage_farmers.php" class="btn" style="background:#f1f5f9; color:#64748b;">Clear</a>
            <?php endif; ?>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Date Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
    <?php if(empty($farmers)): ?>
        <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">No records found.</td></tr>
    <?php else: ?>
        <?php foreach ($farmers as $farmer): ?>
        <tr>
            <td>#<?php echo $farmer['id']; ?></td>
            <td><strong><?php echo htmlspecialchars($farmer['full_name'] ?? 'N/A'); ?></strong></td>
            <td><?php echo htmlspecialchars($farmer['address'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($farmer['contact_number'] ?? 'N/A'); ?></td>
            <td>
                <?php 
                    $date = $farmer['date_joined'] ?? null;
                    echo ($date instanceof DateTime) ? $date->format('M d, Y') : 'N/A';
                ?>
            </td>
            <td style="text-align: right;">
                <a href="edit_farmer.php?id=<?php echo $farmer['id']; ?>" style="color:#7c3aed; margin-right:10px;">Edit</a>
                <a href="delete_farmer.php?id=<?php echo $farmer['id']; ?>" class="btn-delete" onclick="return confirm('Delete this record?')">Delete</a>
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