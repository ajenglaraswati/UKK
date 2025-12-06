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

// Get sales stats
$total_sales = 15;
$total_revenue = 2450.00;
$active_products = count($products);
$seller_rating = 4.9;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Kreava</title>
    <style>
        /* ========== CSS LENGKAP UNTUK SELLER DASHBOARD - WARNA SAMA DENGAN BUYER ========== */
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

        /* Welcome Section untuk Seller */
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

        /* Stats Overview untuk Seller */
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
            background: linear-gradient(135deg, var(--primary), var(--creative));
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
            background: linear-gradient(135deg, var(--primary), var(--creative));
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

        /* Quick Actions untuk Seller */
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

        /* Products Section untuk Seller */
        .products-section {
            margin-bottom: 4rem;
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
            background: linear-gradient(135deg, var(--primary), var(--creative));
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
            
            .section-title {
                font-size: 1.8rem;
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
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
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
                            <i class="fas fa-store"></i>
                            Seller Dashboard
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
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 🚀</h1>
                        <p>Manage your creative products, track sales performance, and grow your business on Kreava.</p>
                        <div class="welcome-actions">
                            <a href="add-product.php" class="btn">
                                <i class="fas fa-plus"></i>
                                Add New Product
                            </a>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i>
                                Manage Products
                            </a>
                        </div>
                    </div>
                    <div class="welcome-graphic">
                        <div class="graphic-card">
                            <i class="fas fa-store"></i>
                            <div class="graphic-text">
                                <span>Seller Pro</span>
                                <small>Level 3 Seller</small>
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
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>15%</span>
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

                    <div class="stat-card success">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>22%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_sales; ?></h3>
                            <p>Total Sales</p>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-arrow-up"></i>
                                <span>18%</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-trend">
                                <i class="fas fa-plus"></i>
                                <span>0.1</span>
                            </div>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $seller_rating; ?></h3>
                            <p>Seller Rating</p>
                            <div class="rating-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= floor($seller_rating) ? 'filled' : ($i == ceil($seller_rating) && $seller_rating != floor($seller_rating) ? 'half-filled' : ''); ?>"></i>
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

                    <a href="products.php" class="action-card">
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

                    <a href="#" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="action-content">
                            <h3>Sales Analytics</h3>
                            <p>View detailed sales reports</p>
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
                            <p>Update your seller profile</p>
                        </div>
                        <div class="action-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Your Products Section -->
            <section class="products-section">
                <div class="section-header">
                    <h2 class="section-title">Your Products</h2>
                    <p class="section-subtitle">Manage and monitor your product listings</p>
                </div>

                <div class="products-grid">
                    <?php if(count($products) > 0): ?>
                        <?php foreach($products as $product): ?>
                        <div class="product-card">
                            <div class="product-header">
                                <div class="product-icon">
                                    <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                                </div>
                                <?php if($product['featured']): ?>
                                <div class="product-badge">Featured</div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-features">
                                <span class="feature-tag"><?php echo ucfirst($product['category']); ?></span>
                                <span class="feature-tag">Digital Product</span>
                                <?php if($product['featured']): ?>
                                <span class="feature-tag">Featured</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-footer">
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-actions">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="product-stats.php?id=<?php echo $product['id']; ?>" class="btn btn-small">
                                        <i class="fas fa-chart-bar"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-cube"></i>
                            <h4>No products yet</h4>
                            <p>Start your selling journey by adding your first product</p>
                            <a href="add-product.php" class="btn">Add Your First Product</a>
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
                        <a href="#" class="footer-link">About</a>
                        <a href="#" class="footer-link">Blog</a>
                        <a href="#" class="footer-link">Careers</a>
                        <a href="#" class="footer-link">Contact</a>
                    </div>

                    <div class="footer-column">
                        <h4>Support</h4>
                        <a href="#" class="footer-link">Help Center</a>
                        <a href="#" class="footer-link">Documentation</a>
                        <a href="#" class="footer-link">Community</a>
                        <a href="#" class="footer-link">Status</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="copyright">
                    &copy; 2024 Kreava. All rights reserved.
                </div>
                <div class="social-links">
                    <a href="#" class="social-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-dribbble"></i>
                    </a>
                    <a href="#" class="social-link">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
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