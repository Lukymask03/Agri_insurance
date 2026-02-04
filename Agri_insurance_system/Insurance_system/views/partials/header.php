<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>AgriInsure</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-green: #198754;
            --dark-green: #146c43;
            --adj-orange: #f39c12;
        }
        
        body { background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* The Green Top Bar from your Dashboard */
        .navbar-custom { 
            background-color: var(--primary-green); 
            padding: 0.7rem 1.5rem; 
        }
        
        .navbar-brand { font-weight: 800; color: white !important; letter-spacing: -0.5px; }

        /* Profile Dropdown Styling (Matching Image 1) */
        .user-profile-btn { 
            background: rgba(255,255,255,0.15); 
            border-radius: 50px; 
            padding: 5px 15px; 
            color: white !important; 
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-profile-btn:hover { background: rgba(255,255,255,0.25); }

        .role-badge { 
            background: white; 
            color: var(--primary-green); 
            font-size: 0.7rem; 
            font-weight: bold; 
            padding: 2px 8px; 
            border-radius: 4px; 
            margin-left: 8px;
            text-transform: uppercase;
        }

        .main-content { flex: 1; padding: 20px 0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <i class="bi bi-shield-fill-check me-2"></i> Agri-Insurance
        </a>

        <?php if ($isLoggedIn): ?>
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <a href="#" class="user-profile-btn dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($userName); ?>
                    <span class="role-badge"><?php echo htmlspecialchars($userRole); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmLogout()">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</nav>

<script>
function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = '../backend/auth.php?action=logout';
    }
}
</script>

<main class="main-content">