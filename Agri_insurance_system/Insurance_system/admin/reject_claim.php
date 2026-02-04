<?php
session_start();
require_once '../backend/db.php';
require_once '../backend/auth.php';

// Security: Only Admin or Adjuster can reject
if (!isLoggedIn() || (!hasRole('admin') && !hasRole('adjuster'))) {
    die("Unauthorized access.");
}

$conn = getConn();
$claim_id = $_GET['id'] ?? 0;

if ($claim_id > 0) {
    // 1. Update status to Rejected and set payout to 0
    $tsql = "UPDATE claims SET status = 'Rejected', approved_payout = 0 WHERE id = ?";
    $params = array($claim_id);
    
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt) {
        // Redirect back to dashboard with a success message
        header("Location: dashboard.php?msg=ClaimRejected");
        exit;
    } else {
        die(print_r(sqlsrv_errors(), true));
    }
} else {
    header("Location: dashboard.php");
    exit;
}
?>