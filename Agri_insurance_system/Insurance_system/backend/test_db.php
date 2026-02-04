<?php
session_start();
require_once 'db.php'; // Using your SSMS connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConn();
    
    // Get data from your form
    $policy_id = $_POST['policy_id'];
    $claim_type = $_POST['claim_type'];
    $description = $_POST['description'];
    $estimated_loss = $_POST['estimated_loss'] ?? 0;
    
    // THE FIX: Use 'claim_date' instead of 'date_filed'
    $tsql = "INSERT INTO claims (policy_id, claim_type, description, estimated_loss, status, claim_date) 
             VALUES (?, ?, ?, ?, 'pending', GETDATE())";
    
    $params = array($policy_id, $claim_type, $description, $estimated_loss);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        // Redirect back to dashboard on success
        header("Location: ../farmer/dashboard.php?status=success");
        exit();
    } else {
        // Log error and redirect with error status
        error_log(print_r(sqlsrv_errors(), true));
        header("Location: ../farmer/file_claim.php?status=error");
        exit();
    }
}
?>