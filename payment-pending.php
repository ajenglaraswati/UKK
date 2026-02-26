<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php';

checkAuth();

$order_id = $_GET['order_id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user']['id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Pending - Kreava</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pending-card {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            border: 1px solid #e2e8f0;
        }

        .pending-icon {
            width: 100px;
            height: 100px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: #92400e;
            font-size: 3rem;
        }

        h1 {
            font-size: 2rem;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        p {
            color: #475569;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .order-info {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #0284c7;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 0.5rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-outline {
            background: white;
            color: #0284c7;
            border: 1px solid #0284c7;
        }
    </style>
</head>
<body>
    <div class="pending-card">
        <div class="pending-icon">
            <i class="fas fa-clock"></i>
        </div>
        <h1>Payment Pending</h1>
        <p>Your payment is being processed. We'll notify you once it's confirmed.</p>
        
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Order Number</span>
                <span class="info-value"><?php echo $order['order_number']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Amount</span>
                <span class="info-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method</span>
                <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
            </div>
        </div>

        <a href="buyer/orders.php" class="btn">View My Orders</a>
        <a href="products.php" class="btn btn-outline">Continue Shopping</a>
    </div>
</body>
</html>