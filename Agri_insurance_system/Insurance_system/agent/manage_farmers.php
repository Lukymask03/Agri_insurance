<?php
session_start();
require_once '../backend/db.php'; // Correctly include your SSMS connection
require_once '../backend/auth.php';

// 1. SECURITY: Check if user is logged in as an agent
if (!isLoggedIn() || !hasRole('agent')) {
    header('Location: ../views/login.php');
    exit;
}

$conn = getConn(); // Get the SQL Server connection from your db.php

// 2. FETCH FARMER DATA: Join users and farmers tables to get full details
$tsql = "SELECT f.id, f.full_name, u.username, u.email, f.address, f.contact_number 
         FROM farmers f 
         JOIN users u ON f.user_id = u.id";

$stmt = sqlsrv_query($conn, $tsql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Agent';
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Farmers - Agricultural Insurance System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f0f2f5; color: #333; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        h1 { color: #1e3a8a; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 15px; border-bottom: 1px solid #edf2f7; text-align: left; }
        th { background: #1e3a8a; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f8fafc; }
        .back-link { text-decoration: none; color: #1e3a8a; font-weight: bold; display: inline-block; margin-bottom: 20px; }
        .btn-view { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="card">
        <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
        <h1>👨‍🌾 Farmer Directory</h1>
        <p>Logged in as: <strong><?php echo htmlspecialchars($full_name); ?></strong></p>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td>
                        <a href="view_farmer.php?id=<?php echo $row['id']; ?>" class="btn-view">View Profile</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>