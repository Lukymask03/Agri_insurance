<?php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    switch($role) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'farmer':
            header('Location: farmer/dashboard.php');
            break;
        case 'agent':
            header('Location: agent/dashboard.php');
            break;
        case 'adjuster':
            header('Location: adjuster/dashboard.php');
            break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agricultural Insurance System - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: url('index_background.png') center/cover no-repeat fixed;
            position: relative;
        }

        /* Overlay for better text readability */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header/Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-img {
            height: 50px;
            width: auto;
        }

        .system-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c5f2d;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn-login {
            background: white;
            color: #2c5f2d;
            border: 2px solid #2c5f2d;
        }

        .btn-login:hover {
            background: #2c5f2d;
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, #2c5f2d 0%, #4a9d4f 100%);
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 95, 45, 0.3);
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .hero-content {
            text-align: center;
            color: white;
            max-width: 800px;
            background: rgba(0, 0, 0, 0.6);
            padding: 3rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            color: #e0e0e0;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            border-radius: 50px;
        }

        /* Features Section */
        .features {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem 2rem;
            margin: 2rem;
            border-radius: 15px;
        }

        .features-title {
            text-align: center;
            font-size: 2rem;
            color: #2c5f2d;
            margin-bottom: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.3rem;
            color: #2c5f2d;
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            color: #666;
            line-height: 1.5;
        }

        /* Footer */
        .footer {
            background: rgba(44, 95, 45, 0.9);
            color: white;
            text-align: center;
            padding: 1.5rem;
            margin-top: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .system-title {
                font-size: 1.2rem;
            }

            .logo-img {
                height: 40px;
            }

            .navbar {
                padding: 1rem;
            }

            .features {
                margin: 1rem;
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="logo-section">
                <img src="../assets/images/agri_insurance_logo.png" alt="Logo" class="logo-img">
                <div class="system-title">Agricultural Insurance System</div>
            </div>
            <div class="nav-buttons">
                <a href="login.php" class="btn btn-login">Login</a>
                <a href="register.php" class="btn btn-register">Register</a>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1 class="hero-title">Protect Your Farm, Secure Your Future</h1>
                <p class="hero-subtitle">
                    Comprehensive insurance coverage for crops and livestock. 
                    Easy claims processing, transparent policies, and dedicated support for Filipino farmers.
                </p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-register btn-hero">Get Started Today</a>
                    <a href="login.php" class="btn btn-login btn-hero">Sign In</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <h2 class="features-title">Why Choose Our Insurance System?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🌾</div>
                    <h3 class="feature-title">Crop Protection</h3>
                    <p class="feature-desc">Comprehensive coverage for all types of crops against natural calamities and diseases</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🐄</div>
                    <h3 class="feature-title">Livestock Insurance</h3>
                    <p class="feature-desc">Protect your livestock investments with our specialized insurance plans</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3 class="feature-title">Fast Claims</h3>
                    <p class="feature-desc">Quick and hassle-free claims processing to get you back on track</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3 class="feature-title">Affordable Premiums</h3>
                    <p class="feature-desc">Flexible payment plans designed for Filipino farmers' needs</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 class="feature-title">Online Management</h3>
                    <p class="feature-desc">Manage your policies, payments, and claims from anywhere</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤝</div>
                    <h3 class="feature-title">Expert Support</h3>
                    <p class="feature-desc">Dedicated agents and adjusters to guide you through every step</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2025 Agricultural Insurance System. All rights reserved.</p>
            <p>Davao Region, Philippines</p>
        </footer>
    </div>
</body>
</html>