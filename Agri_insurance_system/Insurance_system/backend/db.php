<?php
// backend/db.php

// Stop PHP from displaying local file paths on the frontend
error_reporting(0);
ini_set('display_errors', 0);

function getConn() {
    $serverName = "LAPTOP-FFBDJKJ0"; // Your server name from screenshot
    $connectionOptions = array(
        "Database" => "agri_insurance_system",
        "Uid" => "", // Windows Authentication
        "PWD" => "",
        "CharacterSet" => "UTF-8"
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if ($conn === false) {
        // Log error internally instead of dying with a path leak
        error_log(print_r(sqlsrv_errors(), true));
        return false;
    }
    return $conn;
}
?>