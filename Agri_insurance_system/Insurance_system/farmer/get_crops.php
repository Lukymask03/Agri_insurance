<?php
header('Content-Type: application/json');
session_start();

// 1. Check if the farmer is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$farmer_id = $_SESSION['user_id'];

// 2. Database Connection Details
$serverName = "LAPTOP-FFBDJKJ0"; // Based on your SSMS
$connectionOptions = array(
    "Database" => "agri_insurance_system",
    "Uid" => "", // Fill in your DB username if applicable
    "PWD" => ""  // Fill in your DB password if applicable
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    echo json_encode(['error' => 'Connection failed', 'details' => sqlsrv_errors()]);
    exit;
}

// 3. The SQL Query
// We use 'farmer_id' because 'f_id' caused an error in your logs
$sql = "SELECT id, category, type, quantity, location, registered_at 
        FROM crops_livestock 
        WHERE farmer_id = ?";

$params = array($farmer_id);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['error' => 'Query failed', 'details' => sqlsrv_errors()]);
    exit;
}

// 4. Fetch and Format Data
$crops = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Format the date for the frontend
    if ($row['registered_at'] instanceof DateTime) {
        $row['registered_at'] = $row['registered_at']->format('Y-m-d');
    }
    $crops[] = $row;
}

// 5. Send data back to the dashboard
echo json_encode($crops);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>