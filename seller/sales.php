<?php
session_start();
include '../includes/config.php';
include '../includes/functions.php';

checkAuth();
if (!isSeller()) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Get date range from URL or set default (last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales data
try {
    // Summary statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(oi.id) as total_items,
            SUM(oi.subtotal) as total_revenue,
            AVG(oi.subtotal) as average_order_value,
            COUNT(DISTINCT o.user_id) as unique_customers
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ? 
        AND o.status = 'completed'
        AND DATE(o.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $summary = $stmt->fetch();

    // Sales by product
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.price,
            p.icon,
            p.image,
            COUNT(oi.id) as total_sold,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed' AND DATE(o.created_at) BETWEEN ? AND ?
        WHERE p.seller_id = ?
        GROUP BY p.id
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$start_date, $end_date, $user_id]);
    $products_sales = $stmt->fetchAll();

    // Daily sales for chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.created_at) as date,
            COUNT(DISTINCT o.id) as orders,
            SUM(oi.subtotal) as revenue
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE p.seller_id = ? 
        AND o.status = 'completed'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY DATE(o.created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $daily_sales = $stmt->fetchAll();

    // Recent transactions
    $stmt = $pdo->prepare("
        SELECT 
            o.id as order_id,
            o.order_number,
            o.created_at,
            o.total_amount,
            u.name as customer_name,
            u.email as customer_email,
            COUNT(oi.id) as items_count
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.user_id = u.id
        WHERE p.seller_id = ? 
        AND o.status = 'completed'
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id, $start_date, $end_date]);
    $recent_orders = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error fetching sales data: " . $e->getMessage());
    $summary = [
        'total_orders' => 0,
        'total_items' => 0,
        'total_revenue' => 0,
        'average_order_value' => 0,
        'unique_customers' => 0
    ];
    $products_sales = [];
    $daily_sales = [];
    $recent_orders = [];
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sales_report_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['Sales Report - ' . date('d F Y', strtotime($start_date)) . ' to ' . date('d F Y', strtotime($end_date))]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Orders', $summary['total_orders']]);
    fputcsv($output, ['Total Items Sold', $summary['total_items']]);
    fputcsv($output, ['Total Revenue', 'Rp ' . number_format($summary['total_revenue'] ?: 0, 0, ',', '.')]);
    fputcsv($output, ['Average Order Value', 'Rp ' . number_format($summary['average_order_value'] ?: 0, 0, ',', '.')]);
    fputcsv($output, ['Unique Customers', $summary['unique_customers']]);
    fputcsv($output, []);
    
    // Products
    fputcsv($output, ['PRODUCT SALES']);
    fputcsv($output, ['Product Name', 'Price', 'Quantity Sold', 'Total Revenue']);
    foreach ($products_sales as $product) {
        fputcsv($output, [
            $product['name'],
            'Rp ' . number_format($product['price'], 0, ',', '.'),
            $product['total_quantity'] ?: 0,
            'Rp ' . number_format($product['total_revenue'] ?: 0, 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // Daily Sales
    fputcsv($output, ['DAILY SALES']);
    fputcsv($output, ['Date', 'Orders', 'Revenue']);
    foreach ($daily_sales as $day) {
        fputcsv($output, [
            date('d M Y', strtotime($day['date'])),
            $day['orders'],
            'Rp ' . number_format($day['revenue'] ?: 0, 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // Recent Orders
    fputcsv($output, ['RECENT TRANSACTIONS']);
    fputcsv($output, ['Order Number', 'Date', 'Customer', 'Items', 'Total']);
    foreach ($recent_orders as $order) {
        fputcsv($output, [
            $order['order_number'],
            date('d M Y H:i', strtotime($order['created_at'])),
            $order['customer_name'],
            $order['items_count'],
            'Rp ' . number_format($order['total_amount'], 0, ',', '.')
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Kreava Seller</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1280px;
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

        /* Date Filter */
        .date-filter {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            display: block;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .filter-input {
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
        }

        .filter-input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Buttons */
        .btn {
            padding: 0.7rem 1.2rem;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
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
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #0284c7;
            color: #0284c7;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
            border-color: #0284c7;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }

        .stat-sub {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* Chart Section */
        .chart-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Products Table */
        .products-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sales-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .sales-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .sales-table tr:last-child td {
            border-bottom: none;
        }

        .sales-table tr:hover td {
            background: #f8fafc;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.2rem;
        }

        .product-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-name {
            font-weight: 600;
            color: #0f172a;
        }

        .amount {
            font-weight: 600;
            color: #0284c7;
        }

        /* Recent Orders */
        .orders-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .order-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .order-row:hover {
            background: #f8fafc;
        }

        .order-info {
            flex: 2;
        }

        .order-number {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .order-date {
            color: #64748b;
            font-size: 0.85rem;
        }

        .order-customer {
            flex: 2;
        }

        .customer-name {
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }

        .customer-email {
            color: #64748b;
            font-size: 0.8rem;
        }

        .order-items {
            flex: 1;
            text-align: center;
            color: #64748b;
        }

        .order-total {
            flex: 1;
            text-align: right;
            font-weight: 700;
            color: #0284c7;
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
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .date-filter {
                flex-direction: column;
            }

            .filter-actions {
                width: 100%;
            }

            .filter-actions .btn {
                flex: 1;
                justify-content: center;
            }

            .order-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .order-total {
                text-align: left;
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
                <a href="dashboard.php">Dashboard</a>
                <a href="manage-products.php">Products</a>
                <a href="sales.php" class="active">Sales</a>
            </div>

            <div class="nav-buttons">
                <div class="user-dropdown">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="../assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                    </div>
                    <div class="dropdown-menu">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-store"></i>
                            Dashboard
                        </a>
                        <a href="manage-products.php" class="dropdown-item">
                            <i class="fas fa-cube"></i>
                            My Products
                        </a>
                        <a href="add-product.php" class="dropdown-item">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </a>
                        <a href="sales.php" class="dropdown-item">
                            <i class="fas fa-chart-line"></i>
                            Sales Report
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                        <a href="../logout.php" class="dropdown-item">
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
                <h1 class="page-title">Sales Report</h1>
                <a href="?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                    <i class="fas fa-download"></i>
                    Download CSV
                </a>
            </div>

            <!-- Date Filter -->
            <form method="GET" class="date-filter">
                <div class="filter-group">
                    <label class="filter-label">Start Date</label>
                    <input type="date" name="start_date" class="filter-input" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">End Date</label>
                    <input type="date" name="end_date" class="filter-input" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i>
                        Apply Filter
                    </button>
                    <a href="sales.php" class="btn btn-secondary">
                        <i class="fas fa-undo"></i>
                        Reset
                    </a>
                </div>
            </form>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?php echo number_format($summary['total_orders'] ?: 0); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="stat-label">Items Sold</div>
                    <div class="stat-value"><?php echo number_format($summary['total_items'] ?: 0); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">Rp <?php echo number_format($summary['total_revenue'] ?: 0, 0, ',', '.'); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-label">Avg Order Value</div>
                    <div class="stat-value">Rp <?php echo number_format($summary['average_order_value'] ?: 0, 0, ',', '.'); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-label">Unique Customers</div>
                    <div class="stat-value"><?php echo number_format($summary['unique_customers'] ?: 0); ?></div>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="chart-section">
                <div class="chart-header">
                    <h3 class="chart-title">Daily Sales Overview</h3>
                    <span class="stat-sub"><?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></span>
                </div>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Products Sales Table -->
            <div class="products-section">
                <div class="section-header">
                    <h3 class="section-title">Product Performance</h3>
                    <span class="stat-sub"><?php echo count($products_sales); ?> products</span>
                </div>
                <div class="table-responsive">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity Sold</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products_sales) > 0): ?>
                                <?php foreach ($products_sales as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-icon">
                                                <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                                                    <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="amount">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($product['total_quantity'] ?: 0); ?></td>
                                    <td class="amount">Rp <?php echo number_format($product['total_revenue'] ?: 0, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No sales data available for this period
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="orders-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Transactions</h3>
                    <span class="stat-sub">Last 20 orders</span>
                </div>
                <?php if (count($recent_orders) > 0): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="order-row">
                        <div class="order-info">
                            <div class="order-number"><?php echo $order['order_number']; ?></div>
                            <div class="order-date"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="order-customer">
                            <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div class="customer-email"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                        <div class="order-items">
                            <i class="fas fa-cube" style="margin-right: 0.3rem;"></i>
                            <?php echo $order['items_count']; ?> items
                        </div>
                        <div class="order-total">
                            Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #64748b;">
                        No recent transactions
                    </div>
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
        // Initialize chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        <?php
        $chart_labels = [];
        $chart_orders = [];
        $chart_revenue = [];
        
        foreach ($daily_sales as $day) {
            $chart_labels[] = date('d M', strtotime($day['date']));
            $chart_orders[] = $day['orders'];
            $chart_revenue[] = $day['revenue'] ?: 0;
        }
        
        // Reverse for chronological order
        $chart_labels = array_reverse($chart_labels);
        $chart_orders = array_reverse($chart_orders);
        $chart_revenue = array_reverse($chart_revenue);
        ?>
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: <?php echo json_encode($chart_revenue); ?>,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode($chart_orders); ?>,
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>