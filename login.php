<?php 
session_start();
include 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    // Redirect ke dashboard sesuai role
    if ($_SESSION['user']['role'] === 'seller') {
        header('Location: seller/dashboard.php');
    } else {
        header('Location: buyer/dashboard.php');
    }
    exit();
}

// Initialize error variable
$error = '';

// Tampilkan error message dari session jika ada
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar' => $user['avatar'] ?? 'default-avatar.png'
        ];
        
        // Redirect ke dashboard sesuai role
        if ($user['role'] === 'seller') {
            header('Location: seller/dashboard.php');
        } else {
            header('Location: buyer/dashboard.php');
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kreava</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="stars"></div>
        <div class="stars2"></div>
        <div class="stars3"></div>
    </div>

    <!-- Simple Navigation untuk Auth Pages -->
    <nav class="glass-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="logo-wrapper">
                    <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                        <div class="logo-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <span class="logo-text">Kreava</span>
                    </a>
                </div>
            </div>
            
            <div class="nav-auth">
                <a href="index.php" class="auth-link login-btn">
                    <i class="fas fa-home"></i>
                    <span>Back to Home</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="auth-icon">
                            <i class="fas fa-sign-in-alt"></i>
                        </div>
                        <h2>Welcome Back</h2>
                        <p>Sign in to your Kreava account</p>
                    </div>
                    
                    <?php if(!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="forgot-link">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="btn auth-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            Sign In
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Simple Footer untuk Auth Pages -->
    <footer class="simple-footer">
        <div class="footer-container">
            <div class="footer-bottom">
                <div class="copyright">
                    &copy; 2024 Kreava. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>