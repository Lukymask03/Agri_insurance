<?php
// 1. Connection Details
$serverName = "LAPTOP-FFBDJKJ0"; // From your SSMS
$connectionOptions = [
    "Database" => "agri_insurance_system",
    "Uid" => "", // Leave blank if using Windows Auth
    "PWD" => ""
];

try {
    // 2. Establish PDO Connection
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=agri_insurance_system", null, null);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!<br>";

    // 3. Test Requirement #2 (Read 2,000 records)
    $query = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $row = $query->fetch(PDO::FETCH_ASSOC);
    echo "Total Records in Users Table: " . $row['total'];

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        background: #f5f5f5;
    }
    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 3px;
    }
    table {
        background: white;
        margin-top: 10px;
    }
</style>