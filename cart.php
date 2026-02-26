<?php
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/config.php';
include 'includes/functions.php';

// Check authentication
checkAuth();

$user_id = $_SESSION['user']['id'];
$user_role = $_SESSION['user']['role'];
$is_buyer = ($user_role === 'buyer');
$is_seller = ($user_role === 'seller');

// SEMUA USER (buyer dan seller) bisa mengakses cart
// Tidak ada redirect berdasarkan role

$success = '';
$error = '';
$warning = '';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($quantity < 1) $quantity = 1;
    
    try {
        // Check if product exists and get seller info
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Cek apakah seller mencoba membeli produknya sendiri
            if ($is_seller && $product['seller_id'] == $user_id) {
                $_SESSION['error_message'] = "You cannot purchase your own product.";
                header('Location: cart.php');
                exit();
            }
            
            // Check if product already in cart
            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'icon' => $product['icon'] ?? 'cube',
                    'image' => $product['image'] ?? null,
                    'quantity' => $quantity,
                    'seller_id' => $product['seller_id'] ?? 0
                ];
            }
            
            $_SESSION['success_message'] = "Product added to cart successfully!";
        } else {
            $_SESSION['error_message'] = "Product not found.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error adding product to cart.";
    }
    
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $quantity = (int)$quantity;
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    $_SESSION['success_message'] = "Cart updated successfully!";
    header('Location: cart.php');
    exit();
}

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success_message'] = "Item removed from cart.";
    }
    header('Location: cart.php');
    exit();
}

// Handle clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    $_SESSION['success_message'] = "Cart cleared successfully.";
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
$total_items = 0;
$shipping_fee = 0; // Free shipping
$tax_rate = 0.11; // 11% tax (PPN)
$tax = 0;
$total = 0;
$has_own_product = false; // Flag untuk cek apakah ada produk sendiri

foreach ($_SESSION['cart'] as $item) {
    $item_subtotal = $item['price'] * $item['quantity'];
    $subtotal += $item_subtotal;
    $total_items += $item['quantity'];
    
    // Cek apakah seller memiliki produk ini - dengan pengecekan isset
    if ($is_seller && isset($item['seller_id']) && $item['seller_id'] == $user_id) {
        $has_own_product = true;
    }
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax + $shipping_fee;

// Get seller info for each product
$seller_names = [];
if (!empty($_SESSION['cart'])) {
    $seller_ids = [];
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['seller_id']) && $item['seller_id'] > 0) {
            $seller_ids[] = $item['seller_id'];
        }
    }
    $seller_ids = array_unique($seller_ids);
    
    if (!empty($seller_ids)) {
        $placeholders = implode(',', array_fill(0, count($seller_ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id IN ($placeholders)");
        $stmt->execute($seller_ids);
        while ($seller = $stmt->fetch()) {
            $seller_names[$seller['id']] = $seller['name'];
        }
    }
}

// Get messages from session
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['warning_message'])) {
    $warning = $_SESSION['warning_message'];
    unset($_SESSION['warning_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kreava</title>
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
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #475569;
            font-size: 1rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
        }

        /* Cart Container */
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        /* Cart Items */
        .cart-items-section {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .cart-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .cart-items-list {
            padding: 1rem;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 1fr;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: #f8fafc;
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 2rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .item-seller {
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            flex-wrap: wrap;
        }

        .item-seller i {
            color: #0284c7;
            font-size: 0.7rem;
        }

        .own-product-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: #fee2e2;
            color: #b91c1c;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .item-price {
            font-weight: 600;
            color: #0284c7;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-input {
            width: 60px;
            padding: 0.4rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .item-subtotal {
            font-weight: 600;
            color: #0284c7;
        }

        .item-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: white;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-icon:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .update-cart-btn {
            padding: 0.6rem 1.2rem;
            background: white;
            border: 1px solid #0284c7;
            border-radius: 40px;
            color: #0284c7;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-cart-btn:hover {
            background: #0284c7;
            color: white;
        }

        .clear-cart-btn {
            padding: 0.6rem 1.2rem;
            background: white;
            border: 1px solid #ef4444;
            border-radius: 40px;
            color: #ef4444;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .clear-cart-btn:hover {
            background: #ef4444;
            color: white;
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            color: #475569;
        }

        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 2px dashed #e2e8f0;
        }

        .summary-value {
            font-weight: 600;
            color: #0284c7;
        }

        .summary-row.total .summary-value {
            font-size: 1.3rem;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            margin: 1.5rem 0 1rem;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .checkout-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .continue-shopping {
            display: block;
            text-align: center;
            color: #0284c7;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .continue-shopping:hover {
            text-decoration: underline;
        }

        .summary-note {
            margin-top: 1rem;
            padding: 1rem;
            background: #f0f9ff;
            border-radius: 12px;
            font-size: 0.85rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-note i {
            color: #0284c7;
        }

        .warning-message {
            margin-top: 0.5rem;
            padding: 0.8rem;
            background: #fef3c7;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #92400e;
            text-align: center;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 5rem;
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .empty-state .btn {
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-radius: 40px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-state .btn i {
            font-size: 0.9rem;
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
            gap: 2rem;
            margin-bottom: 2rem;
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
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
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

            .cart-header {
                display: none;
            }

            .cart-item {
                grid-template-columns: 1fr;
                gap: 1rem;
                position: relative;
                padding-bottom: 3rem;
            }

            .item-info {
                flex-direction: column;
                text-align: center;
            }

            .item-details {
                text-align: center;
            }

            .item-seller {
                justify-content: center;
            }

            .quantity-control {
                justify-content: center;
            }

            .item-subtotal {
                text-align: center;
            }

            .item-actions {
                position: absolute;
                bottom: 0.5rem;
                right: 0.5rem;
                left: 0.5rem;
                justify-content: center;
            }

            .cart-actions {
                flex-direction: column;
                gap: 1rem;
            }

            .update-cart-btn, .clear-cart-btn {
                width: 100%;
                text-align: center;
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
            <a href="index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="cart.php" class="active">Cart</a>
                <?php if ($is_buyer): ?>
                    <a href="buyer/dashboard.php">Dashboard</a>
                    <a href="buyer/orders.php">Orders</a>
                <?php else: ?>
                    <a href="seller/dashboard.php">Dashboard</a>
                <?php endif; ?>
            </div>

            <div class="nav-buttons">
                <div class="user-dropdown">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                    </div>
                    <div class="dropdown-menu">
                        <?php if ($is_buyer): ?>
                            <a href="buyer/dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                            <a href="buyer/orders.php" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                My Orders
                            </a>
                            <a href="wishlist.php" class="dropdown-item">
                                <i class="fas fa-heart"></i>
                                Wishlist
                            </a>
                        <?php else: ?>
                            <a href="seller/dashboard.php" class="dropdown-item">
                                <i class="fas fa-store"></i>
                                Dashboard
                            </a>
                            <a href="seller/manage-products.php" class="dropdown-item">
                                <i class="fas fa-cube"></i>
                                My Products
                            </a>
                            <a href="seller/add-product.php" class="dropdown-item">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </a>
                        <?php endif; ?>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Shopping Cart</h1>
                <p class="page-subtitle">Review and manage your items before checkout</p>
            </div>

            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($warning): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $warning; ?>
                </div>
            <?php endif; ?>

            <!-- Cart Content -->
            <?php if (empty($_SESSION['cart'])): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="products.php" class="btn">
                        <i class="fas fa-shopping-bag"></i>
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <!-- Cart Grid -->
                <div class="cart-container">
                    <!-- Cart Items -->
                    <div class="cart-items-section">
                        <form method="POST" action="cart.php" id="cart-form">
                            <input type="hidden" name="update_cart" value="1">
                            
                            <div class="cart-header">
                                <span>Product</span>
                                <span>Price</span>
                                <span>Quantity</span>
                                <span>Subtotal</span>
                            </div>

                            <div class="cart-items-list">
                                <?php foreach ($_SESSION['cart'] as $id => $item): 
                                    $item_subtotal = $item['price'] * $item['quantity'];
                                    $is_own_product = ($is_seller && isset($item['seller_id']) && $item['seller_id'] == $user_id);
                                    
                                    // Cek apakah gambar ada - PERBAIKAN PATH
                                    $image_path = '';
                                    $image_exists = false;
                                    
                                    if (!empty($item['image'])) {
                                        $image_path = 'uploads/products/' . $item['image'];
                                        $image_exists = file_exists($image_path);
                                    }
                                ?>
                                <div class="cart-item">
                                    <div class="item-info">
                                        <div class="item-image">
                                            <?php if ($image_exists): ?>
                                                <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-<?php echo $item['icon'] ?? 'cube'; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="item-details">
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <div class="item-seller">
                                                <i class="fas fa-store"></i>
                                                <?php 
                                                $seller_id = isset($item['seller_id']) ? $item['seller_id'] : 0;
                                                echo htmlspecialchars($seller_names[$seller_id] ?? 'Kreava Seller'); 
                                                ?>
                                                <?php if ($is_own_product): ?>
                                                    <span class="own-product-badge">Your Product</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="item-price">
                                        Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                                    </div>
                                    
                                    <div class="quantity-control">
                                        <input type="number" name="quantity[<?php echo $id; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="99" class="quantity-input">
                                    </div>
                                    
                                    <div class="item-subtotal">
                                        Rp <?php echo number_format($item_subtotal, 0, ',', '.'); ?>
                                    </div>
                                    
                                    <div class="item-actions">
                                        <a href="cart.php?remove=<?php echo $id; ?>" class="btn-icon" 
                                           onclick="return confirm('Remove this item from cart?')" title="Remove">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="cart-actions">
                                <button type="submit" class="update-cart-btn">
                                    <i class="fas fa-sync-alt"></i>
                                    Update Cart
                                </button>
                                <a href="cart.php?clear=1" class="clear-cart-btn" 
                                   onclick="return confirm('Clear all items from your cart?')">
                                    <i class="fas fa-trash"></i>
                                    Clear Cart
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3 class="summary-title">Order Summary</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $total_items; ?> items)</span>
                            <span class="summary-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="summary-value">Free</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (11% PPN)</span>
                            <span class="summary-value">Rp <?php echo number_format($tax, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span class="summary-value">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>

                        <?php if ($has_own_product): ?>
                            <button class="checkout-btn" disabled 
                                    onclick="alert('You cannot checkout with your own products. Please remove them from cart.')">
                                <i class="fas fa-credit-card"></i>
                                Cannot Checkout
                            </button>
                            <div class="warning-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                You have your own products in cart. Please remove them to checkout.
                            </div>
                        <?php else: ?>
                            <a href="checkout.php" class="checkout-btn">
                                <i class="fas fa-credit-card"></i>
                                Proceed to Checkout
                            </a>
                        <?php endif; ?>

                        <a href="products.php" class="continue-shopping">
                            <i class="fas fa-arrow-left"></i>
                            Continue Shopping
                        </a>

                        <div class="summary-note">
                            <i class="fas fa-shield-alt"></i>
                            Secure checkout. Your information is protected.
                        </div>
                    </div>
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
                        <li><a href="products.php?category=ui-ux">UI/UX Design</a></li>
                        <li><a href="products.php?category=graphic">Graphic Design</a></li>
                        <li><a href="products.php?category=web">Web Templates</a></li>
                        <li><a href="products.php?category=mobile">Mobile Assets</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="careers.php">Careers</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="terms.php">Terms</a></li>
                        <li><a href="privacy.php">Privacy</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Confirmation for clear cart
        document.querySelectorAll('.clear-cart-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to clear your entire cart?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>