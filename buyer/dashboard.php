<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

checkAuth();
if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Initialize variables with default values
$purchases = [];
$wishlist_count = 0;
$total_downloads = 0;
$average_rating = 0;
$featured_products = [];

try {
    // Get user purchases from database with join to get product names
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_name, oi.quantity, oi.price as item_price
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $purchases = $stmt->fetchAll();
} catch (PDOException $e) {
    // Jika tabel belum ada, set purchases ke array kosong
    $purchases = [];
}

try {
    // Get wishlist count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlist_count = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $wishlist_count = 0;
}

try {
    // Get total downloads
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM downloads WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_downloads = $stmt->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_downloads = 0;
}

try {
    // Get average rating from user's reviews
    $stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $average_rating = $stmt->fetchColumn() ?: 4.8;
} catch (PDOException $e) {
    $average_rating = 4.8;
}

try {
    // Get featured products
    $stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 LIMIT 3");
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_products = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kreava Buyer</title>
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

        .nav-links a.active {
            color: #0284c7;
        }

        .nav-links a.active::after {
            width: 100%;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem 0.5rem 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            border-color: #0284c7;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
        }

        .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1rem;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 0.5rem;
            min-width: 200px;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #475569;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .dropdown-item:hover {
            background: #f8fafc;
            color: #0284c7;
        }

        .dropdown-item:last-child {
            color: #ef4444;
        }

        .dropdown-item:last-child:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 0.5rem 0;
        }

        /* Auth Buttons */
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

        /* Theme Toggle */
        .theme-toggle {
            width: 45px;
            height: 45px;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            background: white;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            background: #f0f9ff;
            color: #0284c7;
            transform: rotate(180deg);
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-content {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .welcome-text h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .welcome-text p {
            color: #475569;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .welcome-actions {
            display: flex;
            gap: 1rem;
        }

        .welcome-graphic {
            flex-shrink: 0;
        }

        .graphic-card {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            min-width: 200px;
        }

        .graphic-card i {
            font-size: 3rem;
            color: #0284c7;
            margin-bottom: 1rem;
        }

        .graphic-card span {
            display: block;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .graphic-card small {
            color: #475569;
        }

        /* Stats Section */
        .stats-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            border-color: #0284c7;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.5rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: #f0f9ff;
            border-radius: 50px;
            color: #059669;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: #64748b;
            font-size: 0.95rem;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .action-card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            border-color: #0284c7;
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .action-content {
            flex: 1;
        }

        .action-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .action-content p {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Recent Activity */
        .activity-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1rem;
        }

        .tab-btn {
            padding: 0.5rem 1.5rem;
            border: none;
            background: none;
            color: #64748b;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 50px;
        }

        .tab-btn:hover {
            color: #0284c7;
            background: #f0f9ff;
        }

        .tab-btn.active {
            color: #0284c7;
            background: #e0f2fe;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .orders-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            border-color: #0284c7;
            transform: translateX(5px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .order-info p {
            color: #64748b;
            font-size: 0.85rem;
        }

        .order-status {
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-paid {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-pending {
            background: #fed7aa;
            color: #92400e;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-amount {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0284c7;
        }

        .items {
            color: #64748b;
            font-size: 0.85rem;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            color: #475569;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-small:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .btn-primary {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-secondary {
            padding: 0.75rem 2rem;
            border: 1px solid #0284c7;
            border-radius: 50px;
            background: white;
            color: #0284c7;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            border-color: #0284c7;
        }

        .product-image {
            height: 180px;
            background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-image i {
            font-size: 4rem;
            color: #0284c7;
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .product-description {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            font-size: 1.2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 3rem 0 1.5rem;
            margin-top: 3rem;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .footer-logo i {
            font-size: 1.8rem;
        }

        .footer-about p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .footer-social {
            display: flex;
            gap: 0.75rem;
        }

        .footer-social a {
            width: 36px;
            height: 36px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: #0284c7;
            color: white;
            transform: translateY(-3px);
        }

        .footer-column h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #0284c7;
            padding-left: 5px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

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

            .welcome-content {
                flex-direction: column;
                text-align: center;
            }

            .welcome-actions {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-about {
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .welcome-content {
                padding: 1.5rem;
            }

            .welcome-text h1 {
                font-size: 1.8rem;
            }

            .welcome-actions {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .order-header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .order-details {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .order-actions {
                width: 100%;
            }

            .btn-small {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="../products.php">Products</a>
                <a href="../cart.php">Cart</a>
                <a href="../orders.php">Orders</a>
            </div>

            <div class="nav-buttons">
                <?php if(isset($_SESSION['user'])): ?>
                <div class="user-dropdown">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="../assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: #94a3b8;"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            My Profile
                        </a>
                        <a href="../orders.php" class="dropdown-item">
                            <i class="fas fa-shopping-bag"></i>
                            My Orders
                        </a>
                        <a href="../wishlist.php" class="dropdown-item">
                            <i class="fas fa-heart"></i>
                            Wishlist
                        </a>
                        <a href="../downloads.php" class="dropdown-item">
                            <i class="fas fa-download"></i>
                            Downloads
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                    <a href="../login.php" class="btn-login">Login</a>
                    <a href="../register.php" class="btn-register">Register</a>
                <?php endif; ?>
                
                <button class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 👋</h1>
                        <p>Ready to discover your next favorite digital asset? Explore our collection and bring your creative projects to life.</p>
                        <div class="welcome-actions">
                            <a href="../products.php" class="btn-primary">
                                <i class="fas fa-rocket"></i>
                                Explore Products
                            </a>
                            <a href="../cart.php" class="btn-secondary">
                                <i class="fas fa-shopping-bag"></i>
                                View Cart
                            </a>
                        </div>
                    </div>
                    <div class="welcome-graphic">
                        <div class="graphic-card">
                            <i class="fas fa-star"></i>
                            <span>Creative Journey</span>
                            <small>Level 2 Explorer</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="stats-section">
                <h2 class="section-title">Your Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +12%
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($purchases); ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +8%
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_downloads ?: 0; ?></h3>
                            <p>Downloads</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-plus"></i>
                                +0
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $wishlist_count ?: 0; ?></h3>
                            <p>Wishlist Items</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +0.2
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($average_rating, 1); ?></h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section>
                <h2 class="section-title">Quick Actions</h2>
                <div class="actions-grid">
                    <a href="../products.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="action-content">
                            <h3>Browse Products</h3>
                            <p>Discover new digital assets</p>
                        </div>
                    </a>

                    <a href="../cart.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="action-content">
                            <h3>View Cart</h3>
                            <p>Manage your purchases</p>
                        </div>
                    </a>

                    <a href="../wishlist.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="action-content">
                            <h3>Wishlist</h3>
                            <p>Your saved items</p>
                        </div>
                    </a>

                    <a href="../profile.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div class="action-content">
                            <h3>Profile Settings</h3>
                            <p>Update your preferences</p>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Recent Activity -->
            <section>
                <h2 class="section-title">Recent Activity</h2>
                
                <div class="activity-tabs">
                    <button class="tab-btn active" data-tab="orders">Orders</button>
                    <button class="tab-btn" data-tab="downloads">Downloads</button>
                    <button class="tab-btn" data-tab="wishlist">Wishlist</button>
                </div>

                <div class="tab-content active" id="orders-tab">
                    <?php if(count($purchases) > 0): ?>
                        <div class="orders-grid">
                            <?php foreach($purchases as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h4><?php echo htmlspecialchars($order['product_name'] ?? 'Product Order'); ?></h4>
                                        <p>Order #<?php echo $order['id']; ?> • <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <span class="order-status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="order-details">
                                    <div class="order-amount">
                                        <span class="amount">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                                        <span class="items"><?php echo $order['quantity'] ?? 1; ?> items</span>
                                    </div>
                                    <div class="order-actions">
                                        <a href="../download.php?order_id=<?php echo $order['id']; ?>" class="btn-small">
                                            <i class="fas fa-download"></i>
                                            Download
                                        </a>
                                        <a href="../order-details.php?id=<?php echo $order['id']; ?>" class="btn-small">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h4>No orders yet</h4>
                            <p>Start shopping to see your orders here</p>
                            <a href="../products.php" class="btn-primary">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-content" id="downloads-tab">
                    <div class="empty-state">
                        <i class="fas fa-download"></i>
                        <h4>No downloads yet</h4>
                        <p>Your downloaded products will appear here</p>
                        <a href="../products.php" class="btn-primary">Browse Products</a>
                    </div>
                </div>

                <div class="tab-content" id="wishlist-tab">
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h4>Wishlist is empty</h4>
                        <p>Start adding products to your wishlist</p>
                        <a href="../products.php" class="btn-primary">Browse Products</a>
                    </div>
                </div>
            </section>

            <!-- Recommended Products -->
            <section>
                <h2 class="section-title">Recommended For You</h2>
                <p style="color: #64748b; margin-bottom: 2rem;">Based on your interests and browsing history</p>

                <?php if(count($featured_products) > 0): ?>
                    <div class="products-grid">
                        <?php foreach($featured_products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <i class="fas fa-<?php echo $product['icon'] ?? 'palette'; ?>"></i>
                            </div>
                            <div class="product-content">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                                <div class="product-actions">
                                    <a href="../product-detail.php?id=<?php echo $product['id']; ?>" class="btn-small">View Details</a>
                                    <form method="POST" action="../add-to-cart.php" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn-small">
                                            <i class="fas fa-shopping-bag"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-cube"></i>
                        <h4>No featured products</h4>
                        <p>Check back later for new recommendations</p>
                        <a href="../products.php" class="btn-primary">Browse Products</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <i class="fas fa-palette"></i>
                        Kreava
                    </div>
                    <p>Empowering creativity through digital assets. Join our community of creators and innovators.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h4>Products</h4>
                    <ul class="footer-links">
                        <li><a href="../products.php?category=ui-ux">UI/UX Design</a></li>
                        <li><a href="../products.php?category=graphic">Graphic Design</a></li>
                        <li><a href="../products.php?category=web">Web Templates</a></li>
                        <li><a href="../products.php?category=mobile">Mobile Assets</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="../about.php">About</a></li>
                        <li><a href="../blog.php">Blog</a></li>
                        <li><a href="../careers.php">Careers</a></li>
                        <li><a href="../contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="../help.php">Help Center</a></li>
                        <li><a href="../terms.php">Terms of Service</a></li>
                        <li><a href="../privacy.php">Privacy Policy</a></li>
                        <li><a href="../faq.php">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button
                    btn.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = btn.dataset.tab + '-tab';
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Theme toggle functionality
            const themeToggle = document.querySelector('.theme-toggle');
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('light-theme');
                const icon = this.querySelector('i');
                if (document.body.classList.contains('light-theme')) {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
            });
        });
    </script>
</body>
</html>