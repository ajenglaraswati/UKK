<?php
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../includes/config.php';
include '../includes/functions.php';

// Check authentication
checkAuth();

// Check if user is buyer
if (!isBuyer()) {
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Initialize variables
$orders = [];
$stats = [
    'total_orders' => 0,
    'completed_orders' => 0,
    'pending_orders' => 0,
    'total_spent' => 0
];

try {
    // Get all orders for this user
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as total_items 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    // Log error jika perlu, tapi set orders ke array kosong
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}

try {
    // Get order statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(total_amount) as total_spent
        FROM orders 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'total_spent' => 0
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = [
        'total_orders' => 0,
        'completed_orders' => 0,
        'pending_orders' => 0,
        'total_spent' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Kreava</title>
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
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            padding: 0.8rem 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
        }

        .logo i {
            font-size: 1.6rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.3rem 0;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
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
            gap: 0.5rem;
            padding: 0.4rem 0.8rem 0.4rem 0.4rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-profile:hover {
            border-color: #0284c7;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
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
            gap: 0.75rem;
            padding: 0.6rem 1rem;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
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
        .btn-login, .btn-register {
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login {
            border: 1px solid #bae6fd;
            background: transparent;
            color: #0369a1;
        }

        .btn-login:hover {
            background: #f0f9ff;
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .btn-register {
            border: none;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        /* Main Content */
        main {
            flex: 1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
            border-color: #0284c7;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 0.2rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.5rem 1.2rem;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            background: white;
            color: #475569;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .filter-tab:hover {
            border-color: #0284c7;
            color: #0284c7;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: transparent;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }

        .search-input {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.85rem;
            width: 200px;
        }

        .search-input:focus {
            outline: none;
            border-color: #0284c7;
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            border-color: #0284c7;
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-number {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .order-number span {
            color: #64748b;
            font-weight: 400;
            font-size: 0.85rem;
        }

        .order-status {
            padding: 0.25rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
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

        .status-processing {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pending {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-cancelled {
            background: #f1f5f9;
            color: #475569;
        }

        .order-items {
            margin: 1rem 0;
            padding: 0.5rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.9rem;
        }

        .item-name {
            color: #1e293b;
        }

        .item-qty {
            color: #64748b;
            font-size: 0.85rem;
        }

        .item-price {
            font-weight: 600;
            color: #0284c7;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-total {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .total-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        .total-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0284c7;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.4rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            background: white;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-small:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: white;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #94a3b8;
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .page-link:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .page-link.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: transparent;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.8rem;
        }

        .footer-logo i {
            font-size: 1.4rem;
        }

        .footer-about p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .footer-social {
            display: flex;
            gap: 0.5rem;
        }

        .footer-social a {
            width: 32px;
            height: 32px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
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
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.4rem;
        }

        .footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: #0284c7;
            padding-left: 3px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 0.8rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                margin-left: 0;
            }

            .search-input {
                width: 100%;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-actions {
                width: 100%;
            }

            .btn-small {
                flex: 1;
                justify-content: center;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="../products.php">Products</a>
                <a href="../cart.php">Cart</a>
                <a href="orders.php" class="active">Orders</a>
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
                            <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                            <a href="orders.php" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                My Orders
                            </a>
                            <a href="../wishlist.php" class="dropdown-item">
                                <i class="fas fa-heart"></i>
                                Wishlist
                            </a>
                            <a href="../profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
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
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">My Orders</h1>
                <a href="../products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    Shop More
                </a>
            </div>

            <!-- Stats Cards -->
            <?php if ($stats['total_orders'] > 0): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['completed_orders'] ?: 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['pending_orders'] ?: 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">Rp <?php echo number_format($stats['total_spent'] ?: 0, 0, ',', '.'); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-tabs">
                    <a href="orders.php" class="filter-tab <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">All</a>
                    <a href="orders.php?status=pending" class="filter-tab <?php echo isset($_GET['status']) && $_GET['status'] == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="orders.php?status=processing" class="filter-tab <?php echo isset($_GET['status']) && $_GET['status'] == 'processing' ? 'active' : ''; ?>">Processing</a>
                    <a href="orders.php?status=completed" class="filter-tab <?php echo isset($_GET['status']) && $_GET['status'] == 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="orders.php?status=cancelled" class="filter-tab <?php echo isset($_GET['status']) && $_GET['status'] == 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                </div>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search orders..." id="searchInput">
                    <button class="btn btn-primary" style="padding: 0.5rem 1rem;" onclick="searchOrders()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Orders List -->
            <?php if (count($orders) > 0): ?>
                <div class="orders-list">
                    <?php 
                    $filtered_orders = $orders;
                    if (isset($_GET['status'])) {
                        $status = $_GET['status'];
                        $filtered_orders = array_filter($orders, function($order) use ($status) {
                            return $order['status'] == $status;
                        });
                    }
                    
                    foreach ($filtered_orders as $order): 
                        // Get order items for this order
                        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                        $stmt->execute([$order['id']]);
                        $items = $stmt->fetchAll();
                    ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">
                                Order #<?php echo $order['order_number']; ?> 
                                <span>• <?php echo date('d M Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <div>
                                    <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    <span class="item-qty"> x<?php echo $item['quantity']; ?></span>
                                </div>
                                <span class="item-price">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="order-total">
                                <span class="total-label">Total:</span>
                                <span class="total-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="order-actions">
                                <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-small">
                                    <i class="fas fa-eye"></i>
                                    View Details
                                </a>
                                <?php if ($order['status'] == 'completed'): ?>
                                <a href="../download.php?order_id=<?php echo $order['id']; ?>" class="btn-small">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                                <?php endif; ?>
                                <?php if ($order['status'] == 'pending'): ?>
                                <a href="../payment.php?order_id=<?php echo $order['id']; ?>" class="btn-small">
                                    <i class="fas fa-credit-card"></i>
                                    Pay Now
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link">...</a>
                    <a href="#" class="page-link">10</a>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>No orders yet</h3>
                    <p>Looks like you haven't placed any orders yet.</p>
                    <a href="../products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Start Shopping
                    </a>
                </div>
            <?php endif; ?>
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
                        <li><a href="../terms.php">Terms</a></li>
                        <li><a href="../privacy.php">Privacy</a></li>
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
        function searchOrders() {
            let searchTerm = document.getElementById('searchInput').value.toLowerCase();
            let orderCards = document.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                let orderNumber = card.querySelector('.order-number').textContent.toLowerCase();
                let items = card.querySelectorAll('.item-name');
                let found = false;
                
                if (orderNumber.includes(searchTerm)) {
                    found = true;
                } else {
                    items.forEach(item => {
                        if (item.textContent.toLowerCase().includes(searchTerm)) {
                            found = true;
                        }
                    });
                }
                
                if (found || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>