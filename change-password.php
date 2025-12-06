<?php
// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$base_path = __DIR__;

// Include required files
require_once $base_path . '/includes/config.php';
require_once $base_path . '/includes/functions.php';

// Cek autentikasi dan role buyer
checkAuth();
if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$error_message = '';
$success_message = '';
$errors = [];

// Proses form change password
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($current_password)) {
        $errors['current_password'] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $errors['new_password'] = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $errors['new_password'] = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $errors['new_password'] = 'Password must contain at least one number';
    } elseif (!preg_match('/[\W_]/', $new_password)) {
        $errors['new_password'] = 'Password must contain at least one special character';
    }
    
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password';
    } elseif ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Cek jika password baru sama dengan password lama
    if ($current_password === $new_password) {
        $errors['new_password'] = 'New password must be different from current password';
    }
    
    // Jika tidak ada error validasi, verifikasi password saat ini
    if (empty($errors)) {
        try {
            // Ambil password hash dari database
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Verifikasi password saat ini
                if (password_verify($current_password, $user['password'])) {
                    // Hash password baru
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password di database
                    $update_stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
                    $update_stmt->execute([
                        ':password' => $new_password_hash,
                        ':id' => $user_id
                    ]);
                    
                    // Log aktivitas
                    logActivity($user_id, 'password_changed', 'User changed password');
                    
                    // Kirim email notifikasi (jika ada fungsi email)
                    if (function_exists('sendPasswordChangeNotification')) {
                        sendPasswordChangeNotification($_SESSION['user']['email'], $_SESSION['user']['name']);
                    }
                    
                    $success_message = 'Password changed successfully!';
                    
                    // Reset form
                    $_POST = [];
                    
                } else {
                    $errors['current_password'] = 'Current password is incorrect';
                }
            } else {
                $error_message = 'User not found';
            }
            
        } catch (PDOException $e) {
            $error_message = 'Error updating password: ' . $e->getMessage();
            error_log('Password change error: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Kreava Buyer</title>
    
    <style>
        /* ========== CSS VARIABLES ========== */
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
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glow: 0 0 20px rgba(124, 58, 237, 0.3);
            --border-radius: 15px;
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        /* Container */
        .main-container {
            min-height: 100vh;
            padding-top: 80px;
            padding-bottom: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
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
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

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

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Navigation */
        .nav-menu {
            display: flex;
            gap: 1rem;
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

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }

        .dropdown-menu {
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

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .dropdown-item:hover {
            background: var(--surface-light);
            color: var(--primary);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--glass-border);
            margin: 6px 0;
        }

        /* Change Password Container */
        .password-container {
            max-width: 500px;
            margin: 2rem auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary);
            gap: 0.75rem;
        }

        .password-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .password-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .card-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .card-header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 1.8rem;
        }

        .card-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .card-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.95rem;
        }

        .password-input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-control.has-error {
            border-color: var(--danger);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Password Requirements */
        .requirements {
            background: var(--surface-light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--glass-border);
        }

        .requirements h4 {
            font-size: 1rem;
            color: var(--text);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .requirement-list {
            list-style: none;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .requirement-item.valid {
            color: var(--success);
        }

        .requirement-item.valid i {
            color: var(--success);
        }

        .requirement-item i {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: none;
            position: relative;
            overflow: hidden;
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
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
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid var(--glass-border);
        }

        .copyright {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .password-card {
                padding: 2rem 1.5rem;
            }
            
            .card-header h1 {
                font-size: 1.8rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .nav-container {
                padding: 0 1rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .nav-link span {
                display: none;
            }
            
            .user-name {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .password-card {
                padding: 1.5rem;
            }
            
            .card-header .icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .requirements {
                padding: 1rem;
            }
        }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="dashboard.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="logo-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <span class="logo-text">Kreava</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="../products.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Products</span>
                </a>
                <a href="../cart.php" class="nav-link">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Cart</span>
                </a>
            </div>

            <div class="user-dropdown">
                <button class="user-profile">
                    <div class="avatar">
                        <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($_SESSION['user']['avatar']); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user" style="color: white; font-size: 14px;"></i>
                        <?php endif; ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        Buyer Dashboard
                    </a>
                    <a href="../profile.php" class="dropdown-item">
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </a>
                    <a href="change-password.php" class="dropdown-item">
                        <i class="fas fa-key"></i>
                        Change Password
                    </a>
                    <a href="../profile.php?tab=avatar" class="dropdown-item">
                        <i class="fas fa-camera"></i>
                        Change Avatar
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-container">
        <div class="container">
            <div class="password-container">
                <!-- Back Link -->
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>

                <!-- Password Card -->
                <div class="password-card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <h1>Change Password</h1>
                        <p>Update your password to keep your account secure</p>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Password Requirements -->
                    <div class="requirements">
                        <h4>Password Requirements:</h4>
                        <ul class="requirement-list">
                            <li class="requirement-item" id="req-length">
                                <i class="fas fa-circle"></i>
                                At least 8 characters
                            </li>
                            <li class="requirement-item" id="req-uppercase">
                                <i class="fas fa-circle"></i>
                                One uppercase letter
                            </li>
                            <li class="requirement-item" id="req-lowercase">
                                <i class="fas fa-circle"></i>
                                One lowercase letter
                            </li>
                            <li class="requirement-item" id="req-number">
                                <i class="fas fa-circle"></i>
                                One number
                            </li>
                            <li class="requirement-item" id="req-special">
                                <i class="fas fa-circle"></i>
                                One special character
                            </li>
                        </ul>
                    </div>

                    <!-- Form -->
                    <form method="POST" action="change-password.php" id="passwordForm">
                        <!-- Current Password -->
                        <div class="form-group">
                            <label class="form-label" for="current_password">
                                <i class="fas fa-lock"></i>
                                Current Password
                            </label>
                            <div class="password-input-group">
                                <i class="input-icon fas fa-lock"></i>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       class="form-control <?php echo isset($errors['current_password']) ? 'has-error' : ''; ?>"
                                       placeholder="Enter your current password"
                                       required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($errors['current_password']); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- New Password -->
                        <div class="form-group">
                            <label class="form-label" for="new_password">
                                <i class="fas fa-lock"></i>
                                New Password
                            </label>
                            <div class="password-input-group">
                                <i class="input-icon fas fa-key"></i>
                                <input type="password" 
                                       id="new_password" 
                                       name="new_password" 
                                       class="form-control <?php echo isset($errors['new_password']) ? 'has-error' : ''; ?>"
                                       placeholder="Create a strong new password"
                                       required>
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($errors['new_password']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="password-strength" style="margin-top: 0.5rem; font-size: 0.85rem;">
                                <div class="strength-meter">
                                    <div class="strength-bar" style="height: 4px; background: var(--surface-light); border-radius: 2px; overflow: hidden;">
                                        <div id="strength-fill" style="height: 100%; width: 0%; background: var(--danger); transition: width 0.3s ease;"></div>
                                    </div>
                                    <div class="strength-text" id="strength-text" style="margin-top: 0.25rem; color: var(--text-secondary);">
                                        Password strength
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">
                                <i class="fas fa-lock"></i>
                                Confirm New Password
                            </label>
                            <div class="password-input-group">
                                <i class="input-icon fas fa-lock"></i>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-control <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>"
                                       placeholder="Confirm your new password"
                                       required>
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <?php echo htmlspecialchars($errors['confirm_password']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="password-match" id="password-match" style="margin-top: 0.5rem; font-size: 0.85rem;">
                                <i class="fas fa-check" style="color: var(--success); display: none;"></i>
                                <span style="color: var(--text-secondary);">Passwords must match</span>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Change Password
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="simple-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; 2024 Kreava. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'fas fa-eye-slash';
                    } else {
                        input.type = 'password';
                        icon.className = 'fas fa-eye';
                    }
                });
            });

            // Password strength checker
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            const passwordMatch = document.getElementById('password-match');
            const matchIcon = passwordMatch.querySelector('i');
            const matchText = passwordMatch.querySelector('span');

            // Requirement elements
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');

            function checkPasswordStrength(password) {
                let score = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[\W_]/.test(password)
                };

                // Update requirement indicators
                reqLength.className = requirements.length ? 'requirement-item valid' : 'requirement-item';
                reqUppercase.className = requirements.uppercase ? 'requirement-item valid' : 'requirement-item';
                reqLowercase.className = requirements.lowercase ? 'requirement-item valid' : 'requirement-item';
                reqNumber.className = requirements.number ? 'requirement-item valid' : 'requirement-item';
                reqSpecial.className = requirements.special ? 'requirement-item valid' : 'requirement-item';

                // Calculate score
                if (requirements.length) score += 20;
                if (requirements.uppercase) score += 20;
                if (requirements.lowercase) score += 20;
                if (requirements.number) score += 20;
                if (requirements.special) score += 20;

                // Update strength meter
                strengthFill.style.width = score + '%';
                
                // Update strength text and color
                if (score <= 40) {
                    strengthFill.style.background = 'var(--danger)';
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = 'var(--danger)';
                } else if (score <= 80) {
                    strengthFill.style.background = 'var(--warning)';
                    strengthText.textContent = 'Good password';
                    strengthText.style.color = 'var(--warning)';
                } else {
                    strengthFill.style.background = 'var(--success)';
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = 'var(--success)';
                }
            }

            function checkPasswordMatch() {
                const password = newPasswordInput.value;
                const confirm = confirmPasswordInput.value;
                
                if (confirm.length === 0) {
                    matchIcon.style.display = 'none';
                    matchText.textContent = 'Passwords must match';
                    matchText.style.color = 'var(--text-secondary)';
                    return;
                }
                
                if (password === confirm && password.length > 0) {
                    matchIcon.style.display = 'inline-block';
                    matchIcon.style.color = 'var(--success)';
                    matchText.textContent = 'Passwords match';
                    matchText.style.color = 'var(--success)';
                } else {
                    matchIcon.style.display = 'none';
                    matchText.textContent = 'Passwords do not match';
                    matchText.style.color = 'var(--danger)';
                }
            }

            // Event listeners
            newPasswordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });

            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            // Form validation
            const form = document.getElementById('passwordForm');
            form.addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current_password').value;
                const newPassword = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Check if new password is different from current
                if (currentPassword === newPassword && currentPassword.length > 0) {
                    e.preventDefault();
                    alert('New password must be different from current password');
                    return false;
                }
                
                // Check if passwords match
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    return false;
                }
                
                // Check password strength
                const score = calculateScore(newPassword);
                if (score < 80) {
                    e.preventDefault();
                    if (!confirm('Your password is not very strong. Are you sure you want to use this password?')) {
                        return false;
                    }
                }
            });

            function calculateScore(password) {
                let score = 0;
                if (password.length >= 8) score += 20;
                if (/[A-Z]/.test(password)) score += 20;
                if (/[a-z]/.test(password)) score += 20;
                if (/[0-9]/.test(password)) score += 20;
                if (/[\W_]/.test(password)) score += 20;
                return score;
            }

            // Theme toggle (if exists elsewhere)
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
                    
                    // Save preference to localStorage
                    localStorage.setItem('theme', document.body.classList.contains('light-theme') ? 'light' : 'dark');
                });
                
                // Load saved theme
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme === 'light') {
                    document.body.classList.add('light-theme');
                    const icon = themeToggle.querySelector('i');
                    icon.className = 'fas fa-sun';
                }
            }
        });
    </script>
</body>
</html>

