<?php 
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'seller') {
        header('Location: seller/dashboard.php');
    } else {
        header('Location: buyer/dashboard.php');
    }
    exit();
}

// Initialize error variable
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = "Email already registered!";
    } else {
        // Create new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
            // Auto login after registration
            $userId = $pdo->lastInsertId();
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'avatar' => 'default-avatar.png'
            ];
            
            // Redirect based on role
            if ($role === 'seller') {
                header('Location: seller/dashboard.php');
            } else {
                header('Location: buyer/dashboard.php');
            }
            exit();
        } else {
            $error = "Registration failed! Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kreava</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            color: #1e293b;
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Modern Glass Navigation */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            padding: 1rem 0;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: #0284c7;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-login {
            padding: 0.75rem 1.5rem;
            border: 1px solid #bae6fd;
            border-radius: 50px;
            background: transparent;
            color: #0369a1;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #f0f9ff;
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .btn-register {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
        }

        /* Auth Container */
        .auth-container {
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }

        .auth-card {
            background: white;
            border-radius: 40px;
            padding: 3rem;
            box-shadow: 0 30px 50px -20px rgba(2, 132, 199, 0.2);
            border: 1px solid #e2e8f0;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .auth-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .auth-icon i {
            font-size: 2.5rem;
            color: #0284c7;
        }

        .auth-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #475569;
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        .alert-error i {
            color: #ef4444;
        }

        /* Form */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .form-group input {
            padding: 1rem 1.2rem;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        /* Role Selection */
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .role-option {
            cursor: pointer;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-card {
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .role-option input[type="radio"]:checked + .role-card {
            border-color: #0284c7;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        }

        .role-card i {
            font-size: 2rem;
            color: #0284c7;
            margin-bottom: 0.75rem;
        }

        .role-card span {
            display: block;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .role-card small {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Checkbox */
        .checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            color: #475569;
            font-size: 0.95rem;
            margin: 0.5rem 0;
        }

        .checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0284c7;
        }

        .checkbox a {
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
        }

        .checkbox a:hover {
            text-decoration: underline;
        }

        .auth-btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer p {
            color: #475569;
        }

        .auth-footer .auth-link {
            color: #0284c7;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer .auth-link:hover {
            color: #0369a1;
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 2rem 0;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-bottom {
            text-align: center;
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }
            
            .auth-card {
                padding: 2rem;
            }
            
            .auth-header h2 {
                font-size: 1.8rem;
            }
            
            .role-selection {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="index.php#features">Features</a>
                <a href="#pricing">Pricing</a>
            </div>

            <div class="nav-buttons">
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Join Kreava</h2>
                    <p>Create your account and start your creative journey</p>
                </div>
                
                <?php if(!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">I want to:</label>
                        <div class="role-selection">
                            <label class="role-option">
                                <input type="radio" name="role" value="buyer" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'buyer') ? 'checked' : ''; ?>>
                                <div class="role-card">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>Buy Products</span>
                                    <small>Explore and purchase digital assets</small>
                                </div>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="seller" <?php echo (isset($_POST['role']) && $_POST['role'] === 'seller') ? 'checked' : ''; ?>>
                                <div class="role-card">
                                    <i class="fas fa-store"></i>
                                    <span>Sell Products</span>
                                    <small>Upload and sell your creations</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <label class="checkbox">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                    </label>
                    
                    <button type="submit" class="auth-btn">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>