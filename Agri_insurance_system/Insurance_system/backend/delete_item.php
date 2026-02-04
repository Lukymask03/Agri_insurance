<?php
require_once 'db.php';
$conn = getConn();

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $tsql = "DELETE FROM crops_livestock WHERE id = ?";
    $stmt = sqlsrv_query($conn, $tsql, array($id));
    
    if($stmt) {
        header("Location: ../farmer/my_crops.php?msg=Deleted");
    }
}
?>