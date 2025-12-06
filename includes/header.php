<?php
session_start();
// Hapus session yang tidak valid
if (isset($_SESSION['user']) && (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['role']))) {
    unset($_SESSION['user']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kreava - Creative Digital Assets</title>
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

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <div class="logo-wrapper">
                    <div class="logo-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <span class="logo-text">Kreava</span>
                </div>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Products</span>
                </a>
                <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                    <?php if($_SESSION['user']['role'] === 'buyer'): ?>
                    <a href="cart.php" class="nav-link cart-link">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Cart</span>
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php else: ?>
                    <a href="seller/dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="nav-auth">
                <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                <div class="user-dropdown">
                    <button class="user-profile">
                        <div class="avatar">
                            <img src="assets/images/<?php echo $_SESSION['user']['avatar'] ?? 'default-avatar.png'; ?>" alt="Profile">
                        </div>
                        <span class="user-name"><?php echo $_SESSION['user']['name']; ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <?php if($_SESSION['user']['role'] === 'buyer'): ?>
                        <a href="buyer/dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            Buyer Dashboard
                        </a>
                        <?php else: ?>
                        <a href="seller/dashboard.php" class="dropdown-item">
                            <i class="fas fa-store"></i>
                            Seller Dashboard
                        </a>
                        <?php endif; ?>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                        <a href="profile.php?tab=avatar" class="dropdown-item">
                            <i class="fas fa-camera"></i>
                            Change Avatar
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="auth-link login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="register.php" class="auth-link register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <?php endif; ?>
                
                <button class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">