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

// Get seller's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

// Get sales stats from database
try {
    // Total sales count
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total_sales 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE oi.product_id IN (SELECT id FROM products WHERE seller_id = ?) 
        AND o.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_sales = $stmt->fetchColumn() ?: 0;
    
    // Total revenue
    $stmt = $pdo->prepare("
        SELECT SUM(oi.subtotal) as total_revenue 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE oi.product_id IN (SELECT id FROM products WHERE seller_id = ?) 
        AND o.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_revenue = $stmt->fetchColumn() ?: 0;
    
    // Available balance (untuk penarikan - bisa diatur misalnya 80% dari revenue)
    $available_balance = $total_revenue * 0.8; // 80% bisa ditarik, 20% fee platform
    
    // Pending withdrawals
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as pending_withdrawals 
        FROM withdrawals 
        WHERE seller_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id]);
    $pending_withdrawals = $stmt->fetchColumn() ?: 0;
    
    // Completed withdrawals
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_withdrawn 
        FROM withdrawals 
        WHERE seller_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_withdrawn = $stmt->fetchColumn() ?: 0;
    
    // Active products
    $active_products = count($products);
    
    // Seller rating from reviews
    $stmt = $pdo->prepare("
        SELECT AVG(r.rating) as avg_rating 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE p.seller_id = ?
    ");
    $stmt->execute([$user_id]);
    $seller_rating = round($stmt->fetchColumn() ?: 4.9, 1);
    
} catch (PDOException $e) {
    $total_sales = 0;
    $total_revenue = 0;
    $available_balance = 0;
    $pending_withdrawals = 0;
    $total_withdrawn = 0;
    $seller_rating = 4.9;
}

// Get withdrawal history
try {
    $stmt = $pdo->prepare("
        SELECT * FROM withdrawals 
        WHERE seller_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $withdrawals = $stmt->fetchAll();
} catch (PDOException $e) {
    $withdrawals = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Kreava</title>
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
            flex-wrap: wrap;
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

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 1rem;
            background: #f0f9ff;
            border-radius: 50px;
            color: #0284c7;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .rating-stars i {
            color: #fbbf24;
        }

        .rating-stars i.empty {
            color: #e2e8f0;
        }

        /* Balance Cards */
        .balance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .balance-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .balance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.3);
            border-color: #0284c7;
        }

        .balance-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .balance-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .balance-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .balance-note {
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .withdraw-btn {
            width: 100%;
            margin-top: 1rem;
            padding: 0.8rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .withdraw-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .withdraw-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Withdraw Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .bank-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .bank-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .bank-row:last-child {
            border-bottom: none;
        }

        .bank-label {
            color: #64748b;
        }

        .bank-value {
            font-weight: 600;
            color: #0f172a;
        }

        /* Withdrawal History */
        .history-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid #e2e8f0;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .history-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
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

        .action-arrow {
            color: #94a3b8;
        }

        .action-card:hover .action-arrow {
            color: #0284c7;
            transform: translateX(3px);
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
            height: 160px;
            background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-image i {
            font-size: 3rem;
            color: #0284c7;
        }

        .product-content {
            padding: 1.5rem;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
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
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-features {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .feature-tag {
            padding: 0.2rem 0.8rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.7rem;
            color: #475569;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0284c7;
        }

        .product-actions {
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

        .product-badge {
            padding: 0.2rem 0.8rem;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Buttons */
        .btn {
            padding: 0.8rem 1.5rem;
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
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            grid-column: 1 / -1;
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
            .stats-grid, .balance-grid {
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

            .stats-grid, .balance-grid {
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
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="withdraw.php">Withdraw</a>
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
                        <a href="withdraw.php" class="dropdown-item">
                            <i class="fas fa-money-bill-wave"></i>
                            Withdraw
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
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 👋</h1>
                        <p>Manage your products, track sales, and withdraw your earnings on Kreava.</p>
                        <div class="welcome-actions">
                            <a href="add-product.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Add New Product
                            </a>
                            <a href="manage-products.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i>
                                Manage Products
                            </a>
                        </div>
                    </div>
                    <div class="welcome-graphic">
                        <div class="graphic-card">
                            <i class="fas fa-store"></i>
                            <span>Seller Pro</span>
                            <small>Level 2 Seller</small>
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
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +15%
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $active_products; ?></h3>
                            <p>Active Products</p>
                            <div class="stat-badge">
                                <i class="fas fa-check"></i>
                                Active Seller
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +22%
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_sales; ?></h3>
                            <p>Total Sales</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                +18%
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3>Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-plus"></i>
                                +0.1
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $seller_rating; ?></h3>
                            <p>Seller Rating</p>
                            <div class="rating-stars">
                                <?php 
                                $full_stars = floor($seller_rating);
                                $half_star = ($seller_rating - $full_stars) >= 0.5;
                                for($i = 1; $i <= 5; $i++): 
                                    if($i <= $full_stars):
                                ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif($i == $full_stars + 1 && $half_star): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; 
                                endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Balance Cards (Pengganti Profile Settings) -->
            <section>
                <h2 class="section-title">Your Balance</h2>
                <div class="balance-grid">
                    <div class="balance-card">
                        <div class="balance-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="balance-label">Available Balance</div>
                        <div class="balance-value">Rp <?php echo number_format($available_balance, 0, ',', '.'); ?></div>
                        <div class="balance-note">Ready to withdraw</div>
                        <button class="withdraw-btn" onclick="openWithdrawModal()" <?php echo $available_balance < 50000 ? 'disabled' : ''; ?>>
                            <i class="fas fa-money-bill-wave"></i>
                            Withdraw Now
                        </button>
                    </div>

                    <div class="balance-card">
                        <div class="balance-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="balance-label">Pending Withdrawals</div>
                        <div class="balance-value">Rp <?php echo number_format($pending_withdrawals, 0, ',', '.'); ?></div>
                        <div class="balance-note">Processing</div>
                    </div>

                    <div class="balance-card">
                        <div class="balance-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="balance-label">Total Withdrawn</div>
                        <div class="balance-value">Rp <?php echo number_format($total_withdrawn, 0, ',', '.'); ?></div>
                        <div class="balance-note">Successfully withdrawn</div>
                    </div>
                </div>
            </section>

            <!-- Withdrawal History -->
            <section class="history-section">
                <h3 class="section-title" style="margin-bottom: 1rem;">Withdrawal History</h3>
                <div class="table-responsive">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Bank</th>
                                <th>Account Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($withdrawals) > 0): ?>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?php echo date('d M Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                    <td class="amount">Rp <?php echo number_format($withdrawal['amount'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['bank_name']); ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['account_number']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                            <?php if ($withdrawal['status'] == 'pending'): ?>
                                                <i class="fas fa-clock"></i> Pending
                                            <?php elseif ($withdrawal['status'] == 'processing'): ?>
                                                <i class="fas fa-spinner"></i> Processing
                                            <?php elseif ($withdrawal['status'] == 'completed'): ?>
                                                <i class="fas fa-check"></i> Completed
                                            <?php elseif ($withdrawal['status'] == 'cancelled'): ?>
                                                <i class="fas fa-times"></i> Cancelled
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #64748b;">
                                        No withdrawal history yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Quick Actions -->
            <section>
                <h2 class="section-title">Quick Actions</h2>
                <div class="actions-grid">
                    <a href="add-product.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h3>Add Product</h3>
                            <p>Create new product listing</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <a href="manage-products.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="action-content">
                            <h3>Manage Products</h3>
                            <p>View and edit your products</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <a href="sales.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="action-content">
                            <h3>Sales Analytics</h3>
                            <p>View detailed sales reports</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <a href="withdraw.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="action-content">
                            <h3>Withdraw</h3>
                            <p>Withdraw your earnings</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Your Products -->
            <section>
                <h2 class="section-title">Your Products</h2>
                <div class="products-grid">
                    <?php if(count($products) > 0): ?>
                        <?php foreach($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                            </div>
                            <div class="product-content">
                                <div class="product-header">
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <?php if($product['featured']): ?>
                                    <div class="product-badge">Featured</div>
                                    <?php endif; ?>
                                </div>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-features">
                                    <span class="feature-tag"><?php echo ucfirst($product['category'] ?? 'Digital'); ?></span>
                                    <span class="feature-tag">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="product-footer">
                                    <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                                    <div class="product-actions">
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn-small" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="product-stats.php?id=<?php echo $product['id']; ?>" class="btn-small" title="Statistics">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-cube"></i>
                            <h4>No products yet</h4>
                            <p>Start your selling journey by adding your first product</p>
                            <a href="add-product.php" class="btn btn-primary">Add Your First Product</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Withdraw Modal -->
    <div class="modal" id="withdrawModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Withdraw Earnings</h3>
                <button class="modal-close" onclick="closeWithdrawModal()">&times;</button>
            </div>
            <form action="process-withdraw.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Amount (Rp)</label>
                    <input type="number" name="amount" class="form-input" 
                           min="50000" max="<?php echo $available_balance; ?>" 
                           step="10000" placeholder="Min. Rp 50.000" required>
                    <small style="color: #64748b;">Available balance: Rp <?php echo number_format($available_balance, 0, ',', '.'); ?></small>
                </div>

                <div class="bank-info">
                    <h4 style="margin-bottom: 1rem;">Bank Account Information</h4>
                    <div class="bank-row">
                        <span class="bank-label">Bank Name</span>
                        <span class="bank-value">BCA</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Account Number</span>
                        <span class="bank-value">1234567890</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Account Holder</span>
                        <span class="bank-value"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                    </div>
                </div>

                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">
                    <i class="fas fa-info-circle"></i> 
                    Withdrawals will be processed within 1-3 business days.
                </p>

                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="closeWithdrawModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>

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
        // Modal functions
        function openWithdrawModal() {
            document.getElementById('withdrawModal').classList.add('active');
        }

        function closeWithdrawModal() {
            document.getElementById('withdrawModal').classList.remove('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = '';
                });
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('withdrawModal');
            if (e.target == modal) {
                closeWithdrawModal();
            }
        });
    </script>
</body>
</html>