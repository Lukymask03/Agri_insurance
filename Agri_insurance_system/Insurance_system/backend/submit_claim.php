<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

// Security check
if (!isLoggedIn() || !hasRole('farmer')) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = getConn();
    
    // Get form data
    $policy_id = $_POST['policy_id'];
    $claim_type = $_POST['claim_type'];
    $description = $_POST['description']; 
    $estimated_loss = $_POST['estimated_loss'] ?? 0;
    
    // --- PHOTO UPLOAD LOGIC ---
    $target_dir = "../uploads/claims/";
    
    // Safety Check: If the folder doesn't exist, create it automatically
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Create a unique name for the photo (e.g., 170500123_damage.jpg)
    $file_name = time() . "_" . basename($_FILES["evidence"]["name"]);
    $target_file = $target_dir . $file_name;
    
    // Check if the file was actually uploaded to the server
    if (move_uploaded_file($_FILES["evidence"]["tmp_name"], $target_file)) {
        
        // Use the exact column names from your SSMS database
        $tsql = "INSERT INTO claims (policy_id, claim_type, claim_description, estimated_loss, status, evidence_file, claim_date) 
                 VALUES (?, ?, ?, ?, 'pending', ?, GETDATE())";
        
        $params = array($policy_id, $claim_type, $description, $estimated_loss, $file_name);
        $stmt = sqlsrv_query($conn, $tsql, $params);

        if ($stmt) {
header("Location: ../farmer/dashboard.php?status=success");
            exit();
        } else {
            die(print_r(sqlsrv_errors(), true));
        }
    } else {
        // This error happens if the 'uploads/claims' folder is missing or protected
        die("Error: The system could not save the photo. Please make sure a folder named 'uploads/claims' exists in your project.");
    }
}
?>