<?php
session_start();
require_once '../backend/auth.php';
require_once '../backend/db.php';

if (!isLoggedIn() || !hasRole('admin')) {
    exit('Unauthorized');
}

if (isset($_GET['id'])) {
    $conn = getConn();
    $id = $_GET['id'];

    $sql = "DELETE FROM farmers WHERE id = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        header('Location: manage_farmers.php?success=deleted');
    } else {
        die("Error deleting record: " . print_r(sqlsrv_errors(), true));
    }
} else {
    header('Location: manage_farmers.php');
}