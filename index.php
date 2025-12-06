<?php
session_start();
// Jika user sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user']) && isset($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'seller') {
        header('Location: seller/dashboard.php');
    } else {
        header('Location: buyer/dashboard.php');
    }
    exit();
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

    <!-- Navigation untuk Guest -->
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
                <a href="#features" class="nav-link">
                    <i class="fas fa-star"></i>
                    <span>Features</span>
                </a>
            </div>

            <div class="nav-auth">
                <a href="login.php" class="auth-link login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="register.php" class="auth-link register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <button class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Hero Section untuk Guest -->
            <section class="hero">
                <div class="hero-content">
                    <div class="hero-badge">
                        <i class="fas fa-bolt"></i>
                        Creative Digital Assets
                    </div>
                    <h1>Unleash Your Creativity with Kreava</h1>
                    <p>Discover premium digital products designed to inspire and elevate your creative projects. From stunning templates to powerful tools, everything you need in one place.</p>
                    <div class="hero-actions">
                        <a href="register.php" class="btn">
                            <i class="fas fa-rocket"></i>
                            Get Started Free
                        </a>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-play"></i>
                            Browse Products
                        </a>
                    </div>
                </div>
            </section>

           <!-- Features Section -->
<section id="features" class="features-section">
    <div class="section-header">
        <h2 class="section-title">Why Choose Kreava?</h2>
        <p class="section-subtitle">Everything you need to bring your creative ideas to life</p>
    </div>

    <div class="features-showcase">
        <!-- Feature 1 - For Buyers -->
        <div class="feature-showcase-card">
            <div class="feature-showcase-content">
                <div class="feature-badge">
                    <i class="fas fa-shopping-bag"></i>
                    For Buyers
                </div>
                <h3>Discover Amazing Digital Assets</h3>
                <p>Get instant access to premium designs, templates, and resources from talented creators worldwide.</p>
                <div class="feature-highlights">
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Instant Downloads</strong>
                            <span>Get your files immediately after purchase</span>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Lifetime Updates</strong>
                            <span>Free updates for all your purchases</span>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Premium Support</strong>
                            <span>24/7 customer support</span>
                        </div>
                    </div>
                </div>
                <a href="register.php" class="btn btn-outline">
                    <i class="fas fa-shopping-bag"></i>
                    Start Shopping
                </a>
            </div>
            <div class="feature-showcase-visual">
                <div class="visual-card buyer-visual">
                    <div class="product-preview">
                        <div class="preview-item">
                            <i class="fas fa-mobile-alt"></i>
                            <span>UI Kit</span>
                        </div>
                        <div class="preview-item">
                            <i class="fas fa-paint-brush"></i>
                            <span>Icons</span>
                        </div>
                        <div class="preview-item">
                            <i class="fas fa-laptop-code"></i>
                            <span>Template</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 2 - For Sellers -->
        <div class="feature-showcase-card reverse">
            <div class="feature-showcase-content">
                <div class="feature-badge seller">
                    <i class="fas fa-store"></i>
                    For Sellers
                </div>
                <h3>Turn Creativity Into Income</h3>
                <p>Upload and sell your digital products to a global community of buyers and grow your creative business.</p>
                <div class="feature-highlights">
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Easy Upload</strong>
                            <span>Simple product management</span>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Real-time Analytics</strong>
                            <span>Track your sales performance</span>
                        </div>
                    </div>
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="highlight-text">
                            <strong>Secure Payments</strong>
                            <span>Safe and reliable transactions</span>
                        </div>
                    </div>
                </div>
                <a href="register.php" class="btn btn-outline">
                    <i class="fas fa-store"></i>
                    Start Selling
                </a>
            </div>
            <div class="feature-showcase-visual">
                <div class="visual-card seller-visual">
                    <div class="stats-preview">
                        <div class="stat-preview">
                            <div class="stat-value">$2,450</div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-preview">
                            <div class="stat-value">156</div>
                            <div class="stat-label">Products Sold</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 3 - Security -->
        <div class="feature-showcase-card security">
            <div class="feature-showcase-content">
                <div class="feature-badge security">
                    <i class="fas fa-shield-alt"></i>
                    Security First
                </div>
                <h3>Your Security is Our Priority</h3>
                <p>We use industry-standard encryption and security measures to protect your data and transactions.</p>
                <div class="security-features">
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="security-text">
                            <h4>Bank-level Security</h4>
                            <p>256-bit SSL encryption for all transactions</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="security-text">
                            <h4>Data Protection</h4>
                            <p>Your personal information is always safe</p>
                        </div>
                    </div>
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="security-text">
                            <h4>24/7 Monitoring</h4>
                            <p>Continuous security monitoring</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="feature-showcase-visual">
                <div class="visual-card security-visual">
                    <div class="security-shield">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="security-badges">
                        <div class="security-badge">
                            <i class="fas fa-check"></i>
                            SSL Secure
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-check"></i>
                            Encrypted
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-check"></i>
                            Protected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

            <!-- Featured Products Preview -->
            <section class="products-preview">
                <div class="section-header">
                    <h2 class="section-title">Popular Products</h2>
                    <p class="section-subtitle">Check out some of our most loved digital assets</p>
                </div>

                <div class="products-grid">
                    <?php
                    include 'includes/config.php';
                    $stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 LIMIT 3");
                    while ($product = $stmt->fetch()):
                    ?>
                    <div class="product-card">
                        <div class="product-header">
                            <div class="product-icon">
                                <i class="fas fa-<?php echo $product['icon'] ?? 'palette'; ?>"></i>
                            </div>
                            <div class="product-badge">Featured</div>
                        </div>
                        
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="product-features">
                            <span class="feature-tag">Digital Download</span>
                            <span class="feature-tag">Lifetime Updates</span>
                        </div>
                        
                        <div class="product-footer">
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-small btn-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="login.php?redirect=<?php echo urlencode('products.php'); ?>" class="btn-small">
                                    <i class="fas fa-shopping-bag"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="section-cta">
                    <a href="products.php" class="btn btn-large">
                        <i class="fas fa-th-large"></i>
                        View All Products
                    </a>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <div class="cta-card">
                    <div class="cta-content">
                        <h2>Ready to Start Your Creative Journey?</h2>
                        <p>Join thousands of creators and buyers in our community. Whether you're looking to create or to sell, Kreava has everything you need.</p>
                        <div class="cta-actions">
                            <a href="register.php" class="btn">
                                <i class="fas fa-user-plus"></i>
                                Sign Up Free
                            </a>
                            <a href="login.php" class="btn btn-secondary">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>