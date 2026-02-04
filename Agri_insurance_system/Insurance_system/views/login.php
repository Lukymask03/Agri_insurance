<?php
session_start();
require_once '../backend/db.php'; 
require_once '../backend/auth.php'; 

$error = '';
$success = '';

// Check if the user just arrived here via a successful logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $success = "You have been successfully logged out.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConn();
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $tsql = "SELECT id, username, password, role FROM dbo.users WHERE username = ? AND status = 'active'";
    $params = array($username);
    $stmt = sqlsrv_query($conn, $tsql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        header("Location: ../" . $user['role'] . "/dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agricultural Insurance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 900px; width: 100%; display: flex; }
        .login-left { flex: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px; color: white; display: flex; flex-direction: column; justify-content: center; }
        .login-left h1 { font-size: 2.5em; margin-bottom: 20px; }
        .login-left p { font-size: 1.1em; line-height: 1.6; opacity: 0.9; }
        .login-right { flex: 1; padding: 60px; }
        .login-header { text-align: center; margin-bottom: 40px; }
        .login-header h2 { color: #333; font-size: 2em; margin-bottom: 10px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-group input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em; }
        .btn-login { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 1.1em; font-weight: 600; cursor: pointer; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #1b5e20; border: 1px solid #c8e6c9; font-weight: bold; }
        .register-link { text-align: center; margin-top: 20px; color: #666; }
        .test-credentials { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #667eea; }
        @media (max-width: 768px) { .login-container { flex-direction: column; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>🌾 Agricultural Insurance System</h1>
            <p>Protecting farmers and their livelihoods through comprehensive insurance coverage.</p>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Login to access your account</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>

                <button type="submit" class="btn-login">Login</button>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register as Farmer</a>
                </div>


               
 <!-- Test Credentials (Remove in production) -->
                <div class="test-credentials">
                    <h4>🔧 Test Credentials</h4>
                    <p><strong>Admin:</strong> <code>admin_user</code> / <code>password</code></p>
                    <p><strong>Agent:</strong> <code>test_agent</code> / <code>password</code></p>
                    <p><strong>Adjuster:</strong> <code>test_adjuster</code> / <code>password</code></p>
                    <p><strong>Farmer:</strong> <code>user_1</code> / <code>password</code></p>
                </div>


            </form>
        </div>
    </div>
</body>
</html>