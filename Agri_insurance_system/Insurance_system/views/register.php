<?php
session_start();
require_once '../backend/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = getConn();
    $username = $_POST['username'];
    $role = $_POST['role'];
    $full_name = $_POST['full_name'];
    $password = $_POST['password']; 

    // 1. Check if username exists using Parameterized Query
    $tsql = "SELECT * FROM dbo.users WHERE username = ?";
    $stmt = sqlsrv_query($conn, $tsql, array($username));

    if (sqlsrv_has_rows($stmt)) {
        echo "<script>alert('Username already taken!');</script>";
    } else {
        // 2. Insert into users table and capture the new ID using OUTPUT INSERTED.id
        $insertUserSql = "INSERT INTO dbo.users (username, password, role, status, full_name) 
                          OUTPUT INSERTED.id 
                          VALUES (?, ?, ?, 'active', ?)";
        
        $params = array($username, $password, $role, $full_name);
        $insertStmt = sqlsrv_query($conn, $insertUserSql, $params);

        if ($insertStmt) {
            $row = sqlsrv_fetch_array($insertStmt, SQLSRV_FETCH_ASSOC);
            $new_user_id = $row['id'];

            // 3. AUTO-CREATE Farmer Profile if the role selected is 'farmer'
            // This ensures dashboard.php has data to display immediately
            if ($role === 'farmer') {
                $farmerSql = "INSERT INTO farmers (user_id, full_name, status) VALUES (?, ?, 'active')";
                sqlsrv_query($conn, $farmerSql, array($new_user_id, $full_name));
            }

            echo "<script>alert('Success! Account created for $full_name.'); window.location.href='login.php';</script>";
        } else {
            // Show error if the SQL fails (likely missing full_name column)
            die(print_r(sqlsrv_errors(), true));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Agri-Insurance</title>
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #3b82f6;
            --text-dark: #1f2937;
            --bg-gray: #f3f4f6;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #f0f4ff 0%, #dbeafe 100%);
            display: flex; 
            min-height: 100vh; 
            align-items: center; 
            justify-content: center; 
            margin: 0; 
        }

        .reg-card { 
            background: white; 
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            width: 100%; 
            max-width: 450px; 
            transition: transform 0.3s ease;
        }

        .reg-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .reg-header h2 { 
            color: var(--primary-blue); 
            margin: 10px 0 5px 0; 
            font-size: 28px;
            font-weight: 800;
        }

        .reg-header p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        .input-group { margin-bottom: 22px; }

        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
            color: var(--text-dark);
            font-size: 14px;
        }

        input, select { 
            width: 100%; 
            padding: 14px; 
            border: 1.5px solid #e5e7eb; 
            border-radius: 12px; 
            box-sizing: border-box; 
            font-size: 15px;
            transition: all 0.2s ease;
            background-color: #f9fafb;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--secondary-blue);
            background-color: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .reg-btn { 
            background: linear-gradient(to right, var(--primary-blue), var(--secondary-blue));
            color: white; 
            border: none; 
            padding: 16px; 
            width: 100%; 
            border-radius: 12px; 
            font-weight: 700; 
            font-size: 16px;
            cursor: pointer; 
            margin-top: 10px; 
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.2);
            transition: all 0.3s ease;
        }

        .reg-btn:hover { 
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
        }

        .login-link { 
            text-align: center; 
            margin-top: 25px; 
            font-size: 14px; 
            color: #4b5563;
        }

        .login-link a { 
            color: var(--primary-blue); 
            text-decoration: none; 
            font-weight: 700; 
        }

        .icon-box {
            background: #eff6ff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 30px;
        }
    </style>
</head>
<body>
    <div class="reg-card">
        <div class="reg-header">
            <div class="icon-box">🌱</div>
            <h2>Create Account</h2>
            <p>Join our agricultural insurance community today</p>
        </div>

        <form action="register.php" method="POST">
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="Enter your full name">
            </div>
            
            <div class="input-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>

            <div class="input-group">
                <label>Account Role</label>
                <select name="role">
                    <option value="farmer">Farmer (Policy Holder)</option>
                    <option value="agent">Insurance Agent (Admin)</option>
                </select>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="reg-btn">Sign Up & Get Started</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>