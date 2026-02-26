<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Check authentication
checkAuth();

// Check if user is buyer
if (!isBuyer()) {
    $_SESSION['error_message'] = "Only buyers can access checkout.";
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Check if cart is empty and no direct product
if (empty($_SESSION['cart']) && !isset($_GET['product_id'])) {
    $_SESSION['error_message'] = "Your cart is empty.";
    header('Location: cart.php');
    exit();
}

// Handle direct buy
$direct_buy = false;
if (isset($_GET['product_id']) && !isset($_SESSION['temp_product'])) {
    $product_id = (int)$_GET['product_id'];
    $direct_buy = true;
    
    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Check if seller trying to buy own product
        if ($_SESSION['user']['id'] == $product['seller_id']) {
            $_SESSION['error_message'] = "You cannot purchase your own product.";
            header('Location: products.php');
            exit();
        }
        
        // Create temp cart with just this product
        $_SESSION['temp_product'] = [
            $product_id => [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'icon' => $product['icon'] ?? 'cube',
                'image' => $product['image'] ?? null,
                'quantity' => 1,
                'seller_id' => $product['seller_id'] ?? 0
            ]
        ];
    } else {
        $_SESSION['error_message'] = "Product not found.";
        header('Location: products.php');
        exit();
    }
}

// Use temp product if exists, otherwise use cart
$cart_items = isset($_SESSION['temp_product']) ? $_SESSION['temp_product'] : $_SESSION['cart'];

// Calculate totals
$subtotal = 0;
$total_items = 0;
$tax_rate = 0.11; // 11% tax
$tax = 0;
$total = 0;

// Array untuk items (untuk ditampilkan di halaman)
$items = [];

foreach ($cart_items as $id => $item) {
    $item_subtotal = $item['price'] * $item['quantity'];
    $subtotal += $item_subtotal;
    $total_items += $item['quantity'];
    
    // Simpan ke array items untuk ditampilkan
    $items[] = [
        'id' => $id,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'icon' => $item['icon'] ?? 'cube',
        'image' => $item['image'] ?? null,
        'subtotal' => $item_subtotal
    ];
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;

// Get user data
$stmt = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    
    // Generate unique order number
    $order_number = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Insert order into database
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, created_at) 
            VALUES (?, ?, ?, 'pending', 'midtrans', 'Digital Delivery', NOW())
        ");
        $stmt->execute([$user_id, $order_number, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $product_id => $item) {
            $item_subtotal = $item['price'] * $item['quantity'];
            $stmt->execute([$order_id, $product_id, $item['name'], $item['price'], $item['quantity'], $item_subtotal]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Prepare Midtrans transaction data
        $midtrans_items = [];
        foreach ($cart_items as $product_id => $item) {
            $midtrans_items[] = [
                'id' => $product_id,
                'name' => $item['name'],
                'price' => (int)$item['price'],
                'quantity' => $item['quantity']
            ];
        }
        
        // Add tax as item
        $midtrans_items[] = [
            'id' => 'tax',
            'name' => 'PPN 11%',
            'price' => (int)$tax,
            'quantity' => 1
        ];
        
        // Prepare customer details from user data
        $customer_details = [
            'first_name' => $user['name'] ?? 'Customer',
            'email' => $user['email'] ?? $_SESSION['user']['email'],
            'phone' => $user['phone'] ?? '081234567890',
        ];
        
        // Prepare transaction details
        $transaction_details = [
            'order_id' => $order_number,
            'gross_amount' => (int)$total
        ];
        
        // Prepare Midtrans parameter
        $params = [
            'transaction_details' => $transaction_details,
            'item_details' => $midtrans_items,
            'customer_details' => $customer_details,
            'enabled_payments' => ['credit_card', 'bca_va', 'bni_va', 'bri_va', 'mandiri_va', 'gopay', 'shopeepay'],
            'credit_card' => [
                'secure' => true
            ]
        ];
        
        // Midtrans API URL (sandbox)
        $url = 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        
        // Server Key
        $server_key = 'SB-Mid-server-lio7AH-PaxhXdNxP2fOvkTye';
        
        // Encode parameters to JSON
        $payload = json_encode($params);
        
        // Prepare curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($server_key . ':')
        ]);
        
        // Execute curl
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 201) {
            $result = json_decode($response, true);
            
            // Save to session
            $_SESSION['snap_token'] = $result['token'];
            $_SESSION['current_order_id'] = $order_id;
            
            // Clear temp product if exists
            if (isset($_SESSION['temp_product'])) {
                unset($_SESSION['temp_product']);
            }
            
            // For form submit, output HTML with snap script
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Processing Payment...</title>
                <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-WI50d0V3-qPPJNNc"></script>
            </head>
            <body>
                <script>
                    snap.pay('<?php echo $result['token']; ?>', {
                        onSuccess: function(result) {
                            window.location.href = 'payment-success.php?order_id=<?php echo $order_id; ?>';
                        },
                        onPending: function(result) {
                            window.location.href = 'payment-pending.php?order_id=<?php echo $order_id; ?>';
                        },
                        onError: function(result) {
                            alert('Payment failed: ' + result.status_message);
                            window.location.href = 'cart.php';
                        }
                    });
                </script>
            </body>
            </html>
            <?php
            exit();
            
        } else {
            // If Midtrans fails, update order status
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
            $error = "Failed to create payment: " . $response;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Get messages from session
$error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kreava</title>
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
            display: flex;
            align-items: center;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }

        .checkout-card {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
            text-align: center;
        }

        .page-subtitle {
            color: #475569;
            text-align: center;
            margin-bottom: 2rem;
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

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        /* Product List */
        .product-list {
            margin-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1.5rem;
        }

        .checkout-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .checkout-item:last-child {
            border-bottom: none;
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
            flex-shrink: 0;
            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.3rem;
        }

        .item-meta {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .item-price {
            font-weight: 700;
            color: #0284c7;
        }

        .item-quantity {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Price Summary */
        .price-summary {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            color: #475569;
        }

        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 2px dashed #e2e8f0;
        }

        .summary-value {
            font-weight: 600;
            color: #0284c7;
        }

        .summary-row.total .summary-value {
            font-size: 1.3rem;
        }

        /* Pay Button */
        .pay-button {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .pay-button i {
            font-size: 1.2rem;
        }

        .back-link {
            display: block;
            text-align: center;
            color: #0284c7;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 2rem 0 1rem;
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

        @media (max-width: 768px) {
            .checkout-card {
                padding: 1.5rem;
            }

            .item-image {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
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
                <a href="cart.php">Cart</a>
                <a href="checkout.php" class="active">Checkout</a>
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
            <div class="checkout-card">
                <h1 class="page-title">Checkout</h1>
                <p class="page-subtitle">Review your order before payment</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Product List -->
                <div class="product-list">
                    <?php foreach ($items as $item): 
                        // Cek apakah gambar ada
                        $image_path = !empty($item['image']) ? 'uploads/products/' . $item['image'] : null;
                        $image_exists = $image_path && file_exists($image_path);
                    ?>
                    <div class="checkout-item">
                        <div class="item-image">
                            <?php if ($image_exists): ?>
                                <img src="<?php echo $image_path; ?>" alt="<?php echo $item['name']; ?>">
                            <?php else: ?>
                                <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-meta">
                                <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Price Summary -->
                <div class="price-summary">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo $total_items; ?> items)</span>
                        <span class="summary-value">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (11% PPN)</span>
                        <span class="summary-value">Rp <?php echo number_format($tax, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="summary-value">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- Pay Button -->
                <form method="POST">
                    <button type="submit" name="pay_now" class="pay-button">
                        <i class="fas fa-credit-card"></i>
                        Pay Now with Midtrans
                    </button>
                </form>

                <?php if ($direct_buy): ?>
                    <a href="products.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Back to Products
                    </a>
                <?php else: ?>
                    <a href="cart.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Back to Cart
                    </a>
                <?php endif; ?>
            </div>
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

    <!-- Midtrans Script -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-WI50d0V3-qPPJNNc"></script>
    
    <?php if (isset($_SESSION['snap_token'])): ?>
    <script>
        snap.pay('<?php echo $_SESSION['snap_token']; ?>', {
            onSuccess: function(result) {
                window.location.href = 'payment-success.php?order_id=<?php echo $_SESSION['current_order_id']; ?>';
            },
            onPending: function(result) {
                window.location.href = 'payment-pending.php?order_id=<?php echo $_SESSION['current_order_id']; ?>';
            },
            onError: function(result) {
                alert('Payment failed: ' + result.status_message);
                window.location.href = 'cart.php';
            }
        });
    </script>
    <?php 
        // Clear session
        unset($_SESSION['snap_token']);
        unset($_SESSION['current_order_id']);
    endif; 
    ?>
</body>
</html>