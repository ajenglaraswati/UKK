<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$address = $input['address'] ?? '';
$city = $input['city'] ?? '';
$postal_code = $input['postal_code'] ?? '';
$payment_method = $input['payment_method'] ?? 'bank_transfer';

// Validate required fields
if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($postal_code)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit();
}

// Calculate total
$subtotal = 0;
$items = [];
foreach ($_SESSION['cart'] as $product_id => $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $items[] = [
        'id' => $product_id,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity']
    ];
}

$tax = $subtotal * 0.11; // 11% tax
$total = $subtotal + $tax;

// Generate unique order number
$order_number = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Insert order into database
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, notes, created_at) 
        VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW())
    ");
    
    $full_address = $address . ', ' . $city . ', ' . $postal_code;
    $stmt->execute([$user_id, $order_number, $total, $payment_method, $full_address, '']);
    $order_id = $pdo->lastInsertId();
    
    // Insert order items
    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        $stmt->execute([$order_id, $product_id, $item['name'], $item['price'], $item['quantity'], $item_subtotal]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare Midtrans transaction data
    $midtrans_items = [];
    foreach ($items as $item) {
        $midtrans_items[] = [
            'id' => $item['id'],
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
    
    // Prepare customer details
    $customer_details = [
        'first_name' => $name,
        'email' => $email,
        'phone' => $phone,
        'billing_address' => [
            'first_name' => $name,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal_code,
            'country_code' => 'IDN'
        ],
        'shipping_address' => [
            'first_name' => $name,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal_code,
            'country_code' => 'IDN'
        ]
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
        'credit_card' => [
            'secure' => true
        ]
    ];
    
    // Set payment method
    if ($payment_method === 'bank_transfer') {
        $params['enabled_payments'] = ['bca_va', 'bni_va', 'bri_va', 'mandiri_va'];
    } elseif ($payment_method === 'credit_card') {
        $params['enabled_payments'] = ['credit_card'];
    } elseif ($payment_method === 'gopay') {
        $params['enabled_payments'] = ['gopay'];
    }
    
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
        
        // Save snap token to session for later use
        $_SESSION['snap_token'] = $result['token'];
        $_SESSION['current_order_id'] = $order_id;
        
        echo json_encode([
            'success' => true,
            'snap_token' => $result['token'],
            'order_id' => $order_id,
            'redirect_url' => $result['redirect_url']
        ]);
    } else {
        // If Midtrans fails, update order status to failed
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create payment: ' . $response
        ]);
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}