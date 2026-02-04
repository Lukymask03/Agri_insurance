<?php
// views/partials/navbar.php
$current_role = $_SESSION['role'] ?? '';
$display_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">
            <i class="bi bi-leaf-fill me-2"></i>Agri-Insurance
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <?php if($current_role == 'admin'): ?>
                        <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                    <?php elseif($current_role == 'adjuster'): ?>
                        <a class="nav-link" href="../adjuster/dashboard.php">Dashboard</a>
                    <?php elseif($current_role == 'farmer'): ?>
                        <a class="nav-link" href="../farmer/dashboard.php">My Farm</a>
                    <?php endif; ?>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-bold" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($display_name); ?> 
                            <span class="badge bg-light text-success ms-1 small"><?php echo ucfirst($current_role); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmLogout()">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        // '../' goes up from /admin/ or /farmer/ to root, then into /backend/
        window.location.href = '../backend/auth.php?action=logout';
    }
}
</script>