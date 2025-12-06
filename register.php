<?php 
session_start();
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
                        
                        <button type="submit" class="btn auth-btn">
                            <i class="fas fa-user-plus"></i>
                            Create Account
                        </button>
                    </form>
                    
                    <div class="auth-footer">
                        <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
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