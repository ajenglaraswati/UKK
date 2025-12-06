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

// Get user purchases
$purchases = [
    ['id' => 1, 'total_amount' => 149.00, 'status' => 'completed', 'created_at' => '2024-01-15', 'item_count' => 1, 'product_name' => 'UI/UX Design System'],
    ['id' => 2, 'total_amount' => 189.00, 'status' => 'paid', 'created_at' => '2024-01-10', 'item_count' => 1, 'product_name' => 'Mobile App UI Kit'],
    ['id' => 3, 'total_amount' => 129.00, 'status' => 'pending', 'created_at' => '2024-01-05', 'item_count' => 1, 'product_name' => 'Illustration Pack']
];

// Sample data
$wishlist_count = 8;
$total_downloads = 12;
$average_rating = 4.8;

// Get recommended products
$stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 LIMIT 3");
$featured_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kreava Buyer</title>
    <style>
        /* ========== CSS LENGKAP DENGAN PERBAIKAN DROPDOWN ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --secondary: #f59e0b;
            --accent: #ec4899;
            --creative: #06d6a0;
            --background: #0f0f1a;
            --surface: #1a1b2e;
            --surface-light: #252742;
            --text: #e2e8f0;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --glow: 0 0 20px rgba(124, 58, 237, 0.3);
        }

        .light-theme {
            --background: #f8fafc;
            --surface: #ffffff;
            --surface-light: #f1f5f9;
            --text: #1e293b;
            --text-secondary: #64748b;
            --glass: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(0, 0, 0, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: linear-gradient(135deg, var(--background) 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, var(--background) 0%, #1a1a2e 50%, #16213e 100%);
            overflow: hidden;
        }

        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #eee, transparent),
                radial-gradient(2px 2px at 40px 70px, #fff, transparent),
                radial-gradient(1px 1px at 90px 40px, #fff, transparent);
            background-repeat: repeat;
            background-size: 200px 100px;
            animation: starsMove 60s linear infinite;
        }

        .stars2 {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 120px 80px, #ccc, transparent),
                radial-gradient(2px 2px at 200px 20px, #fff, transparent);
            background-repeat: repeat;
            background-size: 300px 200px;
            animation: starsMove 100s linear infinite;
        }

        @keyframes starsMove {
            from { transform: translateY(0); }
            to { transform: translateY(-200px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-3px) rotate(2deg); }
            66% { transform: translateY(2px) rotate(-2deg); }
        }

        @keyframes glow {
            from { box-shadow: 0 0 20px rgba(124, 58, 237, 0.3); }
            to { box-shadow: 0 0 30px rgba(124, 58, 237, 0.6); }
        }

        /* Glass Navigation */
        .glass-nav {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        /* Logo Styles */
        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: logoFloat 3s ease-in-out infinite;
        }

        .logo-icon i {
            color: white;
            font-size: 1.3rem;
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, var(--glass), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: var(--glass);
            transform: translateY(-2px);
        }

        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            animation: pulse 2s infinite;
            font-family: 'Poppins', sans-serif;
        }

        /* Auth Buttons */
        .nav-auth {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .auth-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }

        .login-btn {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .login-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--glow);
        }

        .register-btn {
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            border: 2px solid transparent;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--glow);
            background: linear-gradient(135deg, var(--primary-dark), var(--creative));
        }

        /* ========== PERBAIKAN USER DROPDOWN ========== */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text);
            border: none;
            font-family: 'Poppins', sans-serif;
        }

        .user-profile:hover {
            background: var(--surface-light);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .user-dropdown .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .user-dropdown .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .user-dropdown .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
        }

        /* Dropdown Menu - FIXED */
        .user-dropdown .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--surface);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 8px;
            min-width: 200px;
            box-shadow: var(--shadow);
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

        .user-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .user-dropdown .dropdown-item:hover {
            background: var(--surface-light);
            color: var(--primary);
        }

        .user-dropdown .dropdown-item:last-child {
            color: #ef4444;
        }

        .user-dropdown .dropdown-item:last-child:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .user-dropdown .dropdown-divider {
            height: 1px;
            background: var(--glass-border);
            margin: 6px 0;
        }

        /* Theme Toggle */
        .theme-toggle {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            color: var(--text);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .theme-toggle:hover {
            transform: rotate(30deg);
            background: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            min-height: 100vh;
            padding-top: 80px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Welcome Section */
        .welcome-section {
            margin-bottom: 3rem;
        }

        .welcome-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            align-items: center;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .welcome-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .welcome-text h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .welcome-text p {
            color: var(--text-secondary);
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .welcome-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .welcome-graphic {
            display: flex;
            justify-content: center;
        }

        .graphic-card {
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .graphic-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .graphic-text span {
            display: block;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .graphic-text small {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        /* Stats Overview */
        .stats-overview {
            margin-bottom: 3rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .stat-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.premium::before {
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .stat-card.success::before {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-card.warning::before {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-card.info::before {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow), var(--glow);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.premium .stat-icon {
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .stat-card.success .stat-icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .stat-card.warning .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-card.info .stat-icon {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--surface-light);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text);
        }

        .stat-trend i {
            color: #10b981;
        }

        .stat-content h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .stat-progress {
            margin-top: 1rem;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: var(--surface-light);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .stat-progress span {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stat-actions {
            margin-top: 1rem;
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .rating-stars i {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .rating-stars i.filled {
            color: #f59e0b;
        }

        .rating-stars i.half-filled {
            background: linear-gradient(90deg, #f59e0b 50%, var(--text-secondary) 50%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Quick Actions */
        .quick-actions {
            margin-bottom: 3rem;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1.5rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .action-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow), var(--glow);
            border-color: var(--primary);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .action-content {
            flex: 1;
        }

        .action-content h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .action-content p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .action-arrow {
            color: var(--text-secondary);
            transition: transform 0.3s ease;
        }

        .action-card:hover .action-arrow {
            transform: translateX(5px);
            color: var(--primary);
        }

        /* Recent Activity */
        .recent-activity {
            margin-bottom: 4rem;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .view-all-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-all-link:hover {
            gap: 0.75rem;
        }

        .activity-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 1rem;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 1rem 2rem;
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 10px;
            position: relative;
        }

        .tab-btn::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            transition: width 0.3s ease;
        }

        .tab-btn.active {
            color: var(--primary);
        }

        .tab-btn.active::after {
            width: 100%;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: var(--surface-light);
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
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .order-info h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .order-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .order-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-paid {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-amount .amount {
            display: block;
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .order-amount .items {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Modern Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--glow);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-small {
            padding: 0.75rem 1.5rem;
            font-size: 0.9rem;
        }

        /* Recommended Products */
        .recommended-products {
            margin-bottom: 4rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-subtitle {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            font-weight: 400;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .product-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .product-card:hover::before {
            transform: scaleX(1);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow), var(--glow);
            border-color: var(--primary);
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .product-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .product-badge {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .product-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .product-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
        }

        .product-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .feature-tag {
            background: var(--surface-light);
            color: var(--text-secondary);
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid var(--glass-border);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .product-price {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            flex-shrink: 0;
        }

        .product-actions {
            display: flex;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        /* Footer */
        .simple-footer {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--glass-border);
            margin-top: 6rem;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 2rem 2rem;
        }

        .footer-main {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 4rem;
            margin-bottom: 2rem;
        }

        .footer-brand {
            max-width: 300px;
        }

        .footer-tagline {
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            margin-top: 1rem;
            font-weight: 500;
        }

        .footer-links {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .footer-column h4 {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .footer-link {
            display: block;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            padding: 0.25rem 0;
            font-family: 'Poppins', sans-serif;
        }

        .footer-link:hover {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        .copyright {
            color: var(--text-secondary);
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: var(--text);
            margin-bottom: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .empty-state p {
            margin-bottom: 2rem;
        }

        /* ========== RESPONSIVE DESIGN ========== */
        @media (max-width: 968px) {
            .welcome-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }
            
            .welcome-actions {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .order-details {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .order-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .footer-main {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .nav-menu {
                order: 3;
                width: 100%;
                justify-content: center;
                gap: 1rem;
            }
            
            .nav-auth {
                gap: 0.5rem;
            }
            
            .auth-link span {
                display: none;
            }
            
            .user-name {
                display: none;
            }
            
            .user-profile {
                padding: 8px;
            }
            
            .welcome-content {
                padding: 2rem;
            }
            
            .welcome-text h1 {
                font-size: 2rem;
            }
            
            .activity-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .activity-tabs {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .product-actions {
                justify-content: space-between;
            }
            
            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
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
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .nav-menu {
                gap: 0.5rem;
            }
            
            .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            
            .nav-link span {
                display: none;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="stars"></div>
        <div class="stars2"></div>
    </div>

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <div class="logo-wrapper">
                <a href="dashboard.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="logo-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <span class="logo-text">Kreava</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="../products.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Products</span>
                </a>
                <a href="../cart.php" class="nav-link cart-link">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Cart</span>
                    <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="nav-auth">
                <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                <div class="user-dropdown">
                    <button class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="../assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user" style="color: white; font-size: 14px;"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            Buyer Dashboard
                        </a>
                        <a href="../profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                        <a href="../profile.php?tab=avatar" class="dropdown-item">
                            <i class="fas fa-camera"></i>
                            Change Avatar
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="../login.php" class="auth-link login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
                <a href="../register.php" class="auth-link register-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Register</span>
                </a>
                <?php endif; ?>
                
                <button class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-content">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 👋</h1>
                        <p>Ready to discover your next favorite digital asset? Explore our collection and bring your creative projects to life.</p>
                        <div class="welcome-actions">
                            <a href="../products.php" class="btn">
                                <i class="fas fa-rocket"></i>
                                Explore New Products
                            </a>
                            <a href="../cart.php" class="btn btn-secondary">
                                <i class="fas fa-shopping-bag"></i>
                                View Cart
                            </a>
                        </div>
                    </div>
                    <div class="welcome-graphic">
                        <div class="graphic-card">
                            <i class="fas fa-star"></i>
                            <div class="graphic-text">
                                <span>Creative Journey</span>
                                <small>Level 2 Explorer</small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Overview -->
            <section class="stats-overview">
                <div class="stats-grid">
                    <div class="stat-card premium">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>12%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($purchases); ?></h3>
                            <p>Total Orders</p>
                            <div class="stat-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 75%"></div>
                                </div>
                                <span>75% of goal</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>8%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_downloads; ?></h3>
                            <p>Downloads</p>
                            <div class="stat-badge">
                                <i class="fas fa-check"></i>
                                Active Member
                            </div>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-plus"></i>
                                <span>3 new</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $wishlist_count; ?></h3>
                            <p>Wishlist Items</p>
                            <div class="stat-actions">
                                <a href="../wishlist.php" class="btn btn-small">
                                    <i class="fas fa-eye"></i>
                                    View All
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>0.2</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $average_rating; ?></h3>
                            <p>Average Rating</p>
                            <div class="rating-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= floor($average_rating) ? 'filled' : ($i == ceil($average_rating) && $average_rating != floor($average_rating) ? 'half-filled' : ''); ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions">
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
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
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
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
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
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
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
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity">
                <div class="activity-header">
                    <h2 class="section-title">Recent Activity</h2>
                    <a href="../orders.php" class="view-all-link">
                        View All
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="activity-tabs">
                    <button class="tab-btn active" data-tab="orders">Orders</button>
                    <button class="tab-btn" data-tab="downloads">Downloads</button>
                    <button class="tab-btn" data-tab="wishlist">Wishlist</button>
                </div>

                <div class="tab-content active" id="orders-tab">
                    <div class="orders-grid">
                        <?php if(count($purchases) > 0): ?>
                            <?php foreach($purchases as $order): ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div class="order-info">
                                        <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                                        <p>Order #<?php echo $order['id']; ?> • <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="order-status-badge status-<?php echo $order['status']; ?>">
                                        <i class="fas fa-<?php echo $order['status'] === 'completed' ? 'check' : ($order['status'] === 'paid' ? 'credit-card' : 'clock'); ?>"></i>
                                        <?php echo ucfirst($order['status']); ?>
                                    </div>
                                </div>
                                <div class="order-details">
                                    <div class="order-amount">
                                        <span class="amount">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                        <span class="items"><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div class="order-actions">
                                        <a href="../download.php?order_id=<?php echo $order['id']; ?>" class="btn btn-small">
                                            <i class="fas fa-download"></i>
                                            Download
                                        </a>
                                        <a href="../order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-small btn-secondary">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <h4>No orders yet</h4>
                                <p>Start shopping to see your orders here</p>
                                <a href="../products.php" class="btn">Browse Products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-content" id="downloads-tab">
                    <div class="empty-state">
                        <i class="fas fa-download"></i>
                        <h4>No downloads yet</h4>
                        <p>Your downloaded products will appear here</p>
                        <a href="../products.php" class="btn">Browse Products</a>
                    </div>
                </div>

                <div class="tab-content" id="wishlist-tab">
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h4>Wishlist is empty</h4>
                        <p>Start adding products to your wishlist</p>
                        <a href="../products.php" class="btn">Browse Products</a>
                    </div>
                </div>
            </section>

            <!-- Recommended Products -->
            <section class="recommended-products">
                <div class="section-header">
                    <h2 class="section-title">Recommended For You</h2>
                    <p class="section-subtitle">Based on your interests and browsing history</p>
                </div>

                <div class="products-grid">
                    <?php if(count($featured_products) > 0): ?>
                        <?php foreach($featured_products as $product): ?>
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
                                    <a href="../product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-secondary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="../add-to-cart.php" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-small">
                                            <i class="fas fa-shopping-bag"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-cube"></i>
                            <h4>No featured products</h4>
                            <p>Check back later for new recommendations</p>
                            <a href="../products.php" class="btn">Browse Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="simple-footer">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <span class="logo-text">Kreava</span>
                    </div>
                    <p class="footer-tagline">Where creativity meets innovation</p>
                </div>

                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Products</h4>
                        <a href="../products.php?category=ui-ux" class="footer-link">UI/UX Design</a>
                        <a href="../products.php?category=graphic" class="footer-link">Graphic Design</a>
                        <a href="../products.php?category=web" class="footer-link">Web Templates</a>
                        <a href="../products.php?category=mobile" class="footer-link">Mobile Assets</a>
                    </div>

                    <div class="footer-column">
                        <h4>Company</h4>
                        <a href="../about.php" class="footer-link">About</a>
                        <a href="../blog.php" class="footer-link">Blog</a>
                        <a href="../careers.php" class="footer-link">Careers</a>
                        <a href="../contact.php" class="footer-link">Contact</a>
                    </div>

                    <div class="footer-column">
                        <h4>Support</h4>
                        <a href="../help.php" class="footer-link">Help Center</a>
                        <a href="../docs.php" class="footer-link">Documentation</a>
                        <a href="../community.php" class="footer-link">Community</a>
                        <a href="../status.php" class="footer-link">Status</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="copyright">
                    &copy; 2024 Kreava. All rights reserved.
                </div>
                <div class="social-links">
                    <a href="https://twitter.com/kreava" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://instagram.com/kreava" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://dribbble.com/kreava" class="social-link">
                        <i class="fab fa-dribbble"></i>
                    </a>
                    <a href="https://github.com/kreava" class="social-link">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
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

            // Mobile dropdown functionality
            const userDropdowns = document.querySelectorAll('.user-dropdown');
            userDropdowns.forEach(dropdown => {
                dropdown.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const menu = this.querySelector('.dropdown-menu');
                        const isVisible = menu.style.display === 'block';
                        document.querySelectorAll('.dropdown-menu').forEach(m => {
                            m.style.display = 'none';
                        });
                        menu.style.display = isVisible ? 'none' : 'block';
                    }
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-dropdown')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });
        });
    </script>
</body>
</html>