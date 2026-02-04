<?php
session_start();
require_once '../backend/db.php';

header('Content-Type: application/json');

if (isset($_GET['farmer_id'])) {
    $conn = getConn();
    $farmer_id = intval($_GET['farmer_id']);

    // We use farmer_id because that is the confirmed column in your table
    $tsql = "SELECT id, type, category, quantity FROM crops_livestock WHERE farmer_id = ?";
    $params = array($farmer_id);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt === false) {
        echo json_encode(['error' => 'Query failed']);
        exit;
    }

    $items = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }

    echo json_encode($items);
} else {
    echo json_encode(['error' => 'No farmer ID provided']);
}
?>