<?php
// backend/auth.php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * INTEGRATED LOGOUT TRIGGER
 * This catches the ?action=logout request from the dashboards
 */
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout(); 
    // Redirect to login page with a success flag
    header("Location: ../views/login.php?logout=success");
    exit;
}

function login($username, $password) {
    $conn = getConn(); 
    $tsql = "SELECT id, username, password, role, status, full_name FROM dbo.users WHERE username = ?";
    $params = array($username);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt === false) {
        return ['success' => false, 'message' => 'Database Query Error'];
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($user && $user['status'] === 'active' && $password === $user['password']) {
        $_SESSION['user_id']   = $user['id']; 
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name']; 
        $_SESSION['role']      = $user['role'];
        $_SESSION['logged_in'] = true;
        return ['success' => true, 'user' => $user];
    }
    return ['success' => false, 'message' => 'Invalid username or password'];
}

function isLoggedIn() {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Clear all session variables
    $_SESSION = array();
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), "", time() - 3600, "/");
    }
    // Destroy the session
    session_destroy();
}

function hasRole($role) {
    return ($_SESSION['role'] ?? '') === $role;
}
?>