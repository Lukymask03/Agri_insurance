<?php
// backend/controller.php
require_once 'db.php';

/**
 * Requirement #4: Input Validation
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Requirement #4: Real-time data display retrieved from database
 * Fetches farmers using SQL Server Syntax
 */
function getFarmers($conn, $params = []) {
    $search = isset($params['search']) ? sanitize($params['search']) : '';
    
    // T-SQL Syntax for SQL Server
    $tsql = "SELECT f.*, u.username 
            FROM dbo.farmers f 
            JOIN dbo.users u ON f.user_id = u.id 
            WHERE f.full_name LIKE ?";
    
    $params_sql = array("%$search%");
    $stmt = sqlsrv_query($conn, $tsql, $params_sql);
    
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $farmers = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $farmers[] = $row;
    }
    return $farmers;
}

/**
 * Requirement #3: Query with Advanced Features
 */
function getFarmerById($conn, $id) {
    // Requirement #3: Using a Stored Procedure to get details
    $tsql = "{call dbo.get_farmer_details(?)}"; 
    $params = array($id);
    
    $stmt = sqlsrv_query($conn, $tsql, $params);
    if ($stmt === false) return null;

    return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

/**
 * Requirement #3 & #4: Dashboard for data visualization using Advance Queries
 * This calls your VIEW for real-time stats.
 */
function getDashboardStats($conn) {
    // Requirement #3: Utilizing a VIEW created in SSMS
    $tsql = "SELECT * FROM dbo.view_dashboard_stats";
    $stmt = sqlsrv_query($conn, $tsql);

    if ($stmt === false) {
        return [
            'total_farmers' => 0,
            'total_policies' => 0,
            'active_claims' => 0
        ];
    }

    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    return [
        'total_farmers' => $data['total_farmers'] ?? 0,
        'total_policies' => $data['active_policies'] ?? 0, // Using view column
        'active_claims' => $data['pending_claims'] ?? 0    // Using view column
    ];
}

/**
 * Requirement #3: CRUD Operation using Stored Procedure
 * This fulfills the "Update" requirement via Stored Procedure
 */
function updateClaimStatus($conn, $claimId, $status, $amount, $adminId) {
    $tsql = "{call dbo.sp_ProcessClaim(?, ?, ?, ?)}";
    $params = array($claimId, $status, $amount, $adminId);
    
    $stmt = sqlsrv_query($conn, $tsql, $params);
    return ($stmt !== false);
}
?>