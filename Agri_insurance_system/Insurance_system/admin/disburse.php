<?php
session_start();
require_once '../backend/db.php';
$conn = getConn();

if (isset($_GET['id'])) {
    $claim_id = $_GET['id'];
    
    // Update status to Paid
    $tsql = "UPDATE claims SET status = 'Paid' WHERE id = ?";
    $params = array($claim_id);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        // Success: Redirect back to the table with the status message
        header("Location: settlements.php?status=disbursed");
        exit();
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
} else {
    // If no ID is found, just go back to the list
    header("Location: settlements.php");
    exit();
}
?>