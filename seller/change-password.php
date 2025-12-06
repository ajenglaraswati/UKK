<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

checkAuth();

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirmation password do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        try {
            // Get current user data
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success = 'Password updated successfully!';
                
                // Clear form
                $_POST = array();
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Error updating password: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Kreava</title>
    <style>
        /* ========== CSS UNTUK CHANGE PASSWORD PAGE ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --secondary: #f59e0b;
            --accent: #ec4899;
            --creative: #06d6a0;
            --background: #0f0f1a;
            --surface: #1a1b2e;
            --surface-light: #252742;
            --text: #e2e8f0;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glow: 0 0 20px rgba(124, 58, 237, 0.3);
        }

        .light-theme {
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-light: #f1f5f9;
            --text: #1e293b;
            --text-secondary: #64748b;
            --glass: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(0, 0, 0, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: linear-gradient(135deg, var(--background) 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, var(--background) 0%, #1a1a2e 50%, #16213e 100%);
            overflow: hidden;
        }

        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #eee, transparent),
                radial-gradient(2px 2px at 40px 70px, #fff, transparent),
                radial-gradient(1px 1px at 90px 40px, #fff, transparent);
            background-repeat: repeat;
            background-size: 200px 100px;
            animation: starsMove 60s linear infinite;
        }

        .stars2 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 120px 80px, #ccc, transparent),
                radial-gradient(2px 2px at 200px 20px, #fff, transparent);
            background-repeat: repeat;
            background-size: 300px 200px;
            animation: starsMove 100s linear infinite;
        }

        @keyframes starsMove {
            from { transform: translateY(0); }
            to { transform: translateY(-200px); }
        }

        /* Glass Navigation */
        .glass-nav {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        /* Logo Styles */
        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            color: white;
            font-size: 1.3rem;
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--glass), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: var(--glass);
            transform: translateY(-2px);
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text);
            border: none;
            font-family: 'Poppins', sans-serif;
        }

        .user-profile:hover {
            background: var(--surface-light);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .user-dropdown .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .user-dropdown .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-dropdown .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }

        .user-dropdown .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--surface);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 8px;
            min-width: 200px;
            box-shadow: var(--shadow);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .user-dropdown .dropdown-item:hover {
            background: var(--surface-light);
            color: var(--primary);
        }

        .user-dropdown .dropdown-item:last-child {
            color: #ef4444;
        }

        .user-dropdown .dropdown-item:last-child:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .user-dropdown .dropdown-divider {
            height: 1px;
            background: var(--glass-border);
            margin: 6px 0;
        }

        /* Main Content */
        .main-content {
            min-height: 100vh;
            padding-top: 80px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text) 0%, var(--primary) 50%, var(--creative) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Security Card */
        .security-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .security-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .security-card:hover {
            box-shadow: var(--shadow), var(--glow);
        }

        .security-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .security-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .security-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .security-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Form Styles */
        .password-form {
            max-width: 400px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .form-label i {
            color: var(--primary);
            font-size: 1rem;
            width: 20px;
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            transform: translateY(-2px);
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            width: 100%;
            height: 6px;
            background: var(--surface-light);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            border-radius: 3px;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-text {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: right;
        }

        .password-requirements {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--surface-light);
            border-radius: 10px;
            border: 1px solid var(--glass-border);
        }

        .requirements-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-size: 0.9rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .requirement.valid {
            color: #10b981;
        }

        .requirement i {
            font-size: 0.7rem;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        /* Alerts */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .alert i {
            font-size: 1.3rem;
        }

        /* Modern Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* Footer */
        .simple-footer {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            margin-top: 6rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 2rem 2rem;
        }

        .footer-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 4rem;
            margin-bottom: 2rem;
        }

        .footer-brand {
            max-width: 300px;
        }

        .footer-tagline {
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            margin-top: 1rem;
            font-weight: 500;
        }

        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .footer-column h4 {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .footer-link {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            padding: 0.25rem 0;
            font-family: 'Poppins', sans-serif;
        }

        .footer-link:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        .copyright {
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                justify-content: center;
                gap: 1rem;
            }
            
            .nav-auth {
                gap: 0.5rem;
            }
            
            .auth-link span {
                display: none;
            }
            
            .user-name {
                display: none;
            }
            
            .user-profile {
                padding: 8px;
            }
            
            .container {
                padding: 1rem;
            }
            
            .security-card {
                padding: 2rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .footer-main {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .security-card {
                padding: 1.5rem;
            }
            
            .security-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .form-input {
                padding: 0.875rem 1.25rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="stars"></div>
        <div class="stars2"></div>
    </div>

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <div class="logo-wrapper">
                <a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="logo-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <span class="logo-text">Kreava</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="../products.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Products</span>
                </a>
                <?php if(isSeller()): ?>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-store"></i>
                    <span>Seller Dashboard</span>
                </a>
                <?php else: ?>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Buyer Dashboard</span>
                </a>
                <?php endif; ?>
            </div>

            <div class="nav-auth">
                <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                <div class="user-dropdown">
                    <button class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="../assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user" style="color: white; font-size: 14px;"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php if(isSeller()): ?>
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-store"></i>
                            Seller Dashboard
                        </a>
                        <?php else: ?>
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            Buyer Dashboard
                        </a>
                        <?php endif; ?>
                        <a href="../profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                        <a href="change-password.php" class="dropdown-item">
                            <i class="fas fa-lock"></i>
                            Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="../login.php" class="auth-link login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="../register.php" class="auth-link register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Change Password</h1>
                <p class="page-subtitle">Secure your account by updating your password regularly</p>
            </div>

            <!-- Alerts -->
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Security Card -->
            <div class="security-card">
                <div class="security-header">
                    <div class="security-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h2>Update Your Password</h2>
                    <p>Choose a strong password to protect your account</p>
                </div>

                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-key"></i>
                            Current Password
                        </label>
                        <input type="password" name="current_password" class="form-input" 
                               placeholder="Enter your current password" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-lock"></i>
                            New Password
                        </label>
                        <input type="password" name="new_password" id="new_password" class="form-input" 
                               placeholder="Enter your new password" required minlength="6">
                        
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength</div>
                        </div>

                        <div class="password-requirements">
                            <div class="requirements-title">Password Requirements:</div>
                            <div class="requirement" id="reqLength">
                                <i class="fas fa-circle"></i>
                                At least 6 characters
                            </div>
                            <div class="requirement" id="reqUppercase">
                                <i class="fas fa-circle"></i>
                                Contains uppercase letter
                            </div>
                            <div class="requirement" id="reqLowercase">
                                <i class="fas fa-circle"></i>
                                Contains lowercase letter
                            </div>
                            <div class="requirement" id="reqNumber">
                                <i class="fas fa-circle"></i>
                                Contains number
                            </div>
                            <div class="requirement" id="reqSpecial">
                                <i class="fas fa-circle"></i>
                                Contains special character
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-lock"></i>
                            Confirm New Password
                        </label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" 
                               placeholder="Confirm your new password" required minlength="6">
                        <div id="passwordMatch" style="font-size: 0.8rem; margin-top: 0.5rem;"></div>
                    </div>

                    <div class="form-actions">
                        <a href="../profile.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Profile
                        </a>
                        <button type="submit" class="btn" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="simple-footer">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <span class="logo-text">Kreava</span>
                    </div>
                    <p class="footer-tagline">Where creativity meets innovation</p>
                </div>

                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Products</h4>
                        <a href="../products.php?category=ui-ux" class="footer-link">UI/UX Design</a>
                        <a href="../products.php?category=graphic" class="footer-link">Graphic Design</a>
                        <a href="../products.php?category=web" class="footer-link">Web Templates</a>
                        <a href="../products.php?category=mobile" class="footer-link">Mobile Assets</a>
                    </div>

                    <div class="footer-column">
                        <h4>Company</h4>
                        <a href="#" class="footer-link">About</a>
                        <a href="#" class="footer-link">Blog</a>
                        <a href="#" class="footer-link">Careers</a>
                        <a href="#" class="footer-link">Contact</a>
                    </div>

                    <div class="footer-column">
                        <h4>Support</h4>
                        <a href="#" class="footer-link">Help Center</a>
                        <a href="#" class="footer-link">Documentation</a>
                        <a href="#" class="footer-link">Community</a>
                        <a href="#" class="footer-link">Status</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="copyright">
                    &copy; 2024 Kreava. All rights reserved.
                </div>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-dribbble"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            // Password strength checker
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                checkPasswordStrength(password);
                checkPasswordMatch();
            });

            // Password confirmation checker
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 6,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };

                // Update requirement indicators
                document.getElementById('reqLength').className = requirements.length ? 'requirement valid' : 'requirement';
                document.getElementById('reqUppercase').className = requirements.uppercase ? 'requirement valid' : 'requirement';
                document.getElementById('reqLowercase').className = requirements.lowercase ? 'requirement valid' : 'requirement';
                document.getElementById('reqNumber').className = requirements.number ? 'requirement valid' : 'requirement';
                document.getElementById('reqSpecial').className = requirements.special ? 'requirement valid' : 'requirement';

                // Calculate strength
                if (requirements.length) strength += 20;
                if (requirements.uppercase) strength += 20;
                if (requirements.lowercase) strength += 20;
                if (requirements.number) strength += 20;
                if (requirements.special) strength += 20;

                // Update strength bar and text
                strengthFill.style.width = strength + '%';
                
                if (strength < 40) {
                    strengthFill.style.background = '#ef4444';
                    strengthText.textContent = 'Weak';
                    strengthText.style.color = '#ef4444';
                } else if (strength < 80) {
                    strengthFill.style.background = '#f59e0b';
                    strengthText.textContent = 'Medium';
                    strengthText.style.color = '#f59e0b';
                } else {
                    strengthFill.style.background = '#10b981';
                    strengthText.textContent = 'Strong';
                    strengthText.style.color = '#10b981';
                }
            }

            function checkPasswordMatch() {
                const password = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword === '') {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.innerHTML = '<i class="fas fa-check"></i> Passwords match';
                    passwordMatch.style.color = '#10b981';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-times"></i> Passwords do not match';
                    passwordMatch.style.color = '#ef4444';
                }
            }

            // Theme toggle functionality
            const themeToggle = document.querySelector('.theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('light-theme');
                    const icon = this.querySelector('i');
                    if (document.body.classList.contains('light-theme')) {
                        icon.className = 'fas fa-sun';
                    } else {
                        icon.className = 'fas fa-moon';
                    }
                });
            }

            // Mobile dropdown functionality
            const userDropdowns = document.querySelectorAll('.user-dropdown');
            userDropdowns.forEach(dropdown => {
                dropdown.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const menu = this.querySelector('.dropdown-menu');
                        const isVisible = menu.style.display === 'block';
                        document.querySelectorAll('.dropdown-menu').forEach(m => {
                            m.style.display = 'none';
                        });
                        menu.style.display = isVisible ? 'none' : 'block';
                    }
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-dropdown')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });
        });
    </script>
</body>
</html>