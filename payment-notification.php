<?php
include 'includes/config.php';

// Get POST data from Midtrans
$notification = json_decode(file_get_contents('php://input'), true);

if (!$notification) {
    http_response_code(400);
    exit();
}

// Extract transaction details
$order_id = $notification['order_id'] ?? '';
$transaction_status = $notification['transaction_status'] ?? '';
$payment_type = $notification['payment_type'] ?? '';
$fraud_status = $notification['fraud_status'] ?? '';

// Map status
$status = 'pending';
if ($transaction_status == 'capture' || $transaction_status == 'settlement') {
    $status = 'paid';
} elseif ($transaction_status == 'pending') {
    $status = 'pending';
} elseif ($transaction_status == 'deny' || $transaction_status == 'cancel' || $transaction_status == 'expire') {
    $status = 'cancelled';
}

// Update order status in database
try {
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_method = ? WHERE order_number = ?");
    $stmt->execute([$status, $payment_type, $order_id]);
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}