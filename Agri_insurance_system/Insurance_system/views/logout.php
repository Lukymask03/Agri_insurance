<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Clear Session Cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// --- PREVENT BACK BUTTON CACHING ---
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// 4. THE REDIRECT FIX
// If your login.php is inside 'views' folder:
header("Location: ../views/login.php?logout=success");

// IF THE ABOVE STILL 404s, try this (if login is in the root):
// header("Location: ../login.php?logout=success");

exit();
?>