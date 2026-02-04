<?php
// backend/role_guard.php
require_once 'auth.php';

function checkAccess($allowed_role) {
    if (!isLoggedIn()) {
        header("Location: ../views/login.php");
        exit();
    }

    if (!hasRole($allowed_role)) {
        // Requirement #4: Proper redirect for unauthorized access
        // This ensures if an 'agent' tries to access a 'farmer' page, they go to the right place
        $current_role = $_SESSION['role'] ?? 'farmer';
        header("Location: ../views/dashboard.php?error=unauthorized");
        exit();
    }
}
?>