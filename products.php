<?php 
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Perbaikan path - gunakan path absolut atau relatif yang benar
include 'includes/config.php';

// Get category from URL
$current_category = $_GET['category'] ?? 'all';
$category_name = ucfirst(str_replace('-', ' ', $current_category));

// Ambil semua produk dari database
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Mapping ikon ke kategori (karena tidak ada kolom category di database)
$category_map = [
    'desktop' => 'web',
    'laptop-code' => 'web',
    'mobile' => 'mobile',
    'mobile-alt' => 'mobile',
    'icons' => 'icons',
    'font' => 'graphic',
    'image' => 'graphic',
    'paint-brush' => 'graphic',
    'file-alt' => 'graphic',
    'palette' => 'graphic'
];

// Dapatkan kategori unik dari produk
$available_categories = [];
foreach ($products as $product) {
    $icon = $product['icon'] ?? 'file-alt';
    $category = $category_map[$icon] ?? 'graphic';
    if (!in_array($category, $available_categories)) {
        $available_categories[] = $category;
    }
}

// Filter produk berdasarkan kategori
$filtered_products = $products;
if ($current_category !== 'all') {
    $filtered_products = array_filter($products, function($product) use ($current_category, $category_map) {
        $icon = $product['icon'] ?? 'file-alt';
        $product_category = $category_map[$icon] ?? 'graphic';
        return $product_category === $current_category;
    });
}

// Hitung jumlah item di cart untuk badge
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Kreava</title>
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

        /* Modern Glass Navigation */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(56, 189, 248, 0.2);
            padding: 1rem 0;
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
            gap: 0.75rem;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            text-decoration: none;
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #334155;
            font-weight: 500;
            font-size: 1rem;
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

        /* Cart Badge */
        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(249, 115, 22, 0.3);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-login {
            padding: 0.75rem 1.5rem;
            border: 1px solid #bae6fd;
            border-radius: 50px;
            background: transparent;
            color: #0369a1;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #f0f9ff;
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .btn-register {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-dashboard {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        /* Main Content */
        main {
            flex: 1;
        }

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Hero */
        .page-hero {
            text-align: center;
            padding: 3rem 1rem;
            max-width: 800px;
            margin: 0 auto 3rem;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .page-hero h1 {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .page-hero p {
            font-size: 1.1rem;
            color: #475569;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            border-left: 4px solid #10b981;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast i {
            font-size: 1.5rem;
            color: #10b981;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .toast-message {
            color: #64748b;
            font-size: 0.9rem;
        }

        .toast-close {
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .toast-close:hover {
            color: #ef4444;
        }

        /* Filter Options */
        .filter-section {
            background: white;
            border-radius: 30px;
            padding: 1.5rem;
            margin-bottom: 3rem;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
            border: 1px solid #e2e8f0;
        }

        .filter-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-title i {
            color: #0284c7;
        }

        .filter-options {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            background: white;
            color: #475569;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn i {
            color: #0284c7;
            font-size: 1rem;
        }

        .filter-btn:hover {
            border-color: #0284c7;
            background: #f0f9ff;
            color: #0284c7;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .filter-btn.active i {
            color: white;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .product-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: 1px solid #e2e8f0;
            position: relative;
            opacity: 1;
            transform: scale(1);
            height: auto;
            transition: all 0.3s ease;
        }

        .product-card.hidden {
            opacity: 0;
            transform: scale(0.8);
            height: 0;
            margin: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -20px rgba(2, 132, 199, 0.3);
            border-color: #38bdf8;
        }

        /* Product Image */
        .product-image {
            height: 220px;
            background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-image .default-icon {
            font-size: 4rem;
            color: #0284c7;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image .default-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3);
            z-index: 2;
        }

        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #0284c7;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 2;
        }

        .product-content {
            padding: 2rem;
        }

        .product-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .product-description {
            color: #475569;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-features {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .feature-tag {
            padding: 0.4rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            font-size: 0.8rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .feature-tag i {
            color: #0284c7;
            font-size: 0.75rem;
        }

        /* Harga di atas */
        .product-price-section {
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: 20px;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .product-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0284c7;
            line-height: 1.2;
        }

        .product-price small {
            font-size: 1rem;
            font-weight: 400;
            color: #64748b;
        }

        .product-price-label {
            font-size: 0.9rem;
            color: #475569;
            margin-top: 0.25rem;
        }

        /* Button Group */
        .product-button-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .btn-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1.2rem;
            border: none;
        }

        .btn-icon:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            transform: scale(1.1);
            border-color: transparent;
        }

        .btn-icon:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-icon:disabled:hover {
            background: #f8fafc;
            color: #475569;
            transform: none;
        }

        .btn-buy-now {
            flex: 1;
            padding: 0 1.5rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3);
            white-space: nowrap;
        }

        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4);
        }

        .btn-buy-now i {
            font-size: 1rem;
        }

        /* No Products */
        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
        }

        .no-products i {
            font-size: 4rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .no-products h3 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .no-products p {
            color: #64748b;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border-radius: 40px;
            padding: 4rem;
            text-align: center;
            margin: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        }

        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            position: relative;
        }

        .cta-section p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
            font-size: 1.1rem;
            position: relative;
        }

        .cta-section .btn-primary {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            background: white;
            color: #0284c7;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
        }

        .cta-section .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 4rem 0 2rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }

        .footer-logo i {
            font-size: 2rem;
        }

        .footer-about p {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .footer-social {
            display: flex;
            gap: 1rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            transform: translateY(-5px);
            border-color: transparent;
        }

        .footer h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-links a i {
            color: #0284c7;
            font-size: 0.8rem;
        }

        .footer-links a:hover {
            color: #0284c7;
            transform: translateX(5px);
        }

        .footer-newsletter p {
            color: #475569;
            margin-bottom: 1rem;
        }

        .newsletter-form {
            display: flex;
            gap: 0.5rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
        }

        .newsletter-form input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .newsletter-form button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 15px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(2, 132, 199, 0.3);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .footer-bottom a {
            color: #0284c7;
            text-decoration: none;
        }

        .footer-bottom a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-hero h1 {
                font-size: 2.5rem;
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
            
            .page-hero h1 {
                font-size: 2rem;
            }
            
            .filter-options {
                justify-content: center;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .product-button-group {
                flex-wrap: wrap;
            }
            
            .btn-buy-now {
                width: 100%;
                padding: 1rem;
            }
            
            .cta-section {
                padding: 3rem 2rem;
            }
            
            .cta-section h2 {
                font-size: 2rem;
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
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .toast {
                top: auto;
                bottom: 20px;
                right: 20px;
                left: 20px;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <div class="toast-content">
            <div class="toast-title" id="toast-title">Success!</div>
            <div class="toast-message" id="toast-message">Product added to cart</div>
        </div>
        <div class="toast-close" onclick="hideToast()">
            <i class="fas fa-times"></i>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="products.php" class="active">Products</a>
                <a href="cart.php" class="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="buyer/orders.php">Orders</a>
            </div>

            <div class="nav-buttons">
                <?php if(isset($_SESSION['user'])): ?>
                    <?php if($_SESSION['user']['role'] === 'seller'): ?>
                        <a href="seller/dashboard.php" class="btn-dashboard">
                            <i class="fas fa-store"></i>
                            Dashboard
                        </a>
                    <?php else: ?>
                        <a href="buyer/dashboard.php" class="btn-dashboard">
                            <i class="fas fa-user"></i>
                            Dashboard
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Page Hero -->
            <div class="page-hero">
                <h1><?php echo $category_name === 'All' ? 'All Digital Products' : $category_name . ' Products'; ?></h1>
                <p>Discover premium digital assets created by talented designers and developers</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-title">
                    <i class="fas fa-filter"></i>
                    Filter by Category
                </div>
                <div class="filter-options" id="filterOptions">
                    <button class="filter-btn <?php echo $current_category === 'all' ? 'active' : ''; ?>" data-filter="all">
                        <i class="fas fa-th"></i>
                        All Products
                    </button>
                    
                    <?php if(in_array('web', $available_categories)): ?>
                    <button class="filter-btn <?php echo $current_category === 'web' ? 'active' : ''; ?>" data-filter="web">
                        <i class="fas fa-laptop-code"></i>
                        Web Templates
                    </button>
                    <?php endif; ?>
                    
                    <?php if(in_array('mobile', $available_categories)): ?>
                    <button class="filter-btn <?php echo $current_category === 'mobile' ? 'active' : ''; ?>" data-filter="mobile">
                        <i class="fas fa-mobile-alt"></i>
                        Mobile Assets
                    </button>
                    <?php endif; ?>
                    
                    <?php if(in_array('icons', $available_categories)): ?>
                    <button class="filter-btn <?php echo $current_category === 'icons' ? 'active' : ''; ?>" data-filter="icons">
                        <i class="fas fa-icons"></i>
                        Icons & Illustrations
                    </button>
                    <?php endif; ?>
                    
                    <?php if(in_array('graphic', $available_categories)): ?>
                    <button class="filter-btn <?php echo $current_category === 'graphic' ? 'active' : ''; ?>" data-filter="graphic">
                        <i class="fas fa-paint-brush"></i>
                        Graphic Design
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid" id="productsGrid">
                <?php if (count($filtered_products) > 0): ?>
                    <?php foreach ($filtered_products as $product): 
                        // Tentukan kategori untuk data attribute
                        $icon = $product['icon'] ?? 'file-alt';
                        $product_category = $category_map[$icon] ?? 'graphic';
                        
                        // Mapping ikon ke kategori display
                        $category_icons = [
                            'web' => 'laptop-code',
                            'mobile' => 'mobile-alt',
                            'icons' => 'icons',
                            'graphic' => 'paint-brush'
                        ];
                        
                        // Nama kategori untuk display
                        $category_names = [
                            'web' => 'Web Template',
                            'mobile' => 'Mobile Asset',
                            'icons' => 'Icon Set',
                            'graphic' => 'Graphic Design'
                        ];
                        
                        // Gambar produk (jika ada)
                        $product_image = !empty($product['image']) ? 'uploads/products/' . $product['image'] : null;
                    ?>
                    <div class="product-card" data-category="<?php echo $product_category; ?>" data-product-id="<?php echo $product['id']; ?>">
                        <div class="product-image">
                            <?php if($product['featured']): ?>
                            <div class="product-badge">Featured</div>
                            <?php endif; ?>
                            
                            <div class="category-badge">
                                <i class="fas fa-<?php echo $category_icons[$product_category] ?? 'palette'; ?>"></i>
                                <?php echo $category_names[$product_category] ?? ucfirst($product_category); ?>
                            </div>
                            
                            <?php if ($product_image && file_exists($product_image)): ?>
                                <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-<?php echo $icon; ?> default-icon"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-content">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-features">
                                <span class="feature-tag"><i class="fas fa-download"></i> Digital Download</span>
                                <span class="feature-tag"><i class="fas fa-sync"></i> Lifetime Updates</span>
                                <span class="feature-tag"><i class="fas fa-headset"></i> Premium Support</span>
                            </div>
                            
                            <!-- Harga di atas button -->
                            <div class="product-price-section">
                                <div class="product-price">
                                    Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                                </div>
                                <div class="product-price-label">
                                    Harga termurah di pasaran
                                </div>
                            </div>
                            
                            <!-- Button Group: Detail, Keranjang, Beli Sekarang -->
                            <div class="product-button-group">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-icon" title="Detail Produk">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                                    <?php if($_SESSION['user']['role'] === 'buyer'): ?>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-icon" title="Tambah ke Keranjang">
                                            <i class="fas fa-shopping-bag"></i>
                                        </button>
                                        
                                        <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="btn-buy-now" title="Beli Sekarang">
                                            <i class="fas fa-bolt"></i>
                                            Beli Sekarang
                                        </a>
                                    <?php else: ?>
                                        <button class="btn-icon" disabled title="Seller tidak dapat membeli produk">
                                            <i class="fas fa-shopping-bag"></i>
                                        </button>
                                        <button class="btn-icon" disabled title="Seller tidak dapat membeli produk">
                                            <i class="fas fa-bolt"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-icon" title="Login untuk membeli">
                                        <i class="fas fa-shopping-bag"></i>
                                    </a>
                                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-buy-now" title="Login untuk membeli">
                                        <i class="fas fa-bolt"></i>
                                        Beli Sekarang
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <h3>No Products Found</h3>
                        <p>There are no products in this category yet. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CTA Section -->
            <div class="cta-section">
                <h2>Can't Find What You're Looking For?</h2>
                <p>Browse our complete collection or contact us for custom requests</p>
                <a href="#" class="btn-primary">
                    <i class="fas fa-headset"></i>
                    Contact Support
                </a>
            </div>
        </div>
    </main>

    <!-- Enhanced Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <i class="fas fa-palette"></i>
                        Kreava
                    </div>
                    <p>Empowering creativity through premium digital assets. Join thousands of creators and innovators in our community.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div>
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="products.php"><i class="fas fa-chevron-right"></i> Products</a></li>
                        <li><a href="index.php#features"><i class="fas fa-chevron-right"></i> Features</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="help.php"><i class="fas fa-chevron-right"></i> Help Center</a></li>
                        <li><a href="terms.php"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                        <li><a href="privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-newsletter">
                    <h4>Stay Updated</h4>
                    <p>Subscribe to get updates on new products and special offers</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your email address">
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved. Made with <i class="fas fa-heart" style="color: #ef4444;"></i> for creators</p>
            </div>
        </div>
    </footer>

    <script>
        // Toast notification functions
        function showToast(title, message, isSuccess = true) {
            const toast = document.getElementById('toast');
            const toastTitle = document.getElementById('toast-title');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = toast.querySelector('i');
            
            if (isSuccess) {
                toastIcon.className = 'fas fa-check-circle';
                toastIcon.style.color = '#10b981';
                toast.style.borderLeftColor = '#10b981';
            } else {
                toastIcon.className = 'fas fa-exclamation-circle';
                toastIcon.style.color = '#ef4444';
                toast.style.borderLeftColor = '#ef4444';
            }
            
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            toast.classList.add('show');
            
            // Auto hide after 3 seconds
            setTimeout(hideToast, 3000);
        }

        function hideToast() {
            document.getElementById('toast').classList.remove('show');
        }

        // Add to cart function
        function addToCart(productId) {
            fetch('add-to-cart-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    } else {
                        // Create badge if not exists
                        const cartLink = document.querySelector('.cart-link');
                        const badge = document.createElement('span');
                        badge.className = 'cart-badge';
                        badge.textContent = data.cart_count;
                        cartLink.appendChild(badge);
                    }
                    
                    // Show success toast
                    showToast('Success!', data.message, true);
                } else {
                    // Show error toast
                    showToast('Error!', data.message, false);
                    
                    // If not logged in, redirect after 2 seconds
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error!', 'Failed to add product to cart', false);
            });
        }

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const productCards = document.querySelectorAll('.product-card');
            const pageHero = document.querySelector('.page-hero h1');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filterValue = this.getAttribute('data-filter');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter products with smooth transition
                    productCards.forEach(card => {
                        if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                            card.classList.remove('hidden');
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                    
                    // Update URL without page reload
                    const newUrl = new URL(window.location);
                    if (filterValue === 'all') {
                        newUrl.searchParams.delete('category');
                    } else {
                        newUrl.searchParams.set('category', filterValue);
                    }
                    window.history.pushState({}, '', newUrl);
                    
                    // Update page title
                    const buttonText = this.textContent.trim().replace(/[0-9]/g, '').trim();
                    if (filterValue === 'all') {
                        pageHero.textContent = 'All Digital Products';
                    } else {
                        pageHero.textContent = buttonText + ' Products';
                    }
                });
            });
            
            // Handle browser back/forward buttons
            window.addEventListener('popstate', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const category = urlParams.get('category') || 'all';
                
                // Update active button
                filterButtons.forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.getAttribute('data-filter') === category) {
                        btn.classList.add('active');
                        
                        const buttonText = btn.textContent.trim().replace(/[0-9]/g, '').trim();
                        if (category === 'all') {
                            pageHero.textContent = 'All Digital Products';
                        } else {
                            pageHero.textContent = buttonText + ' Products';
                        }
                    }
                });
                
                // Filter products
                productCards.forEach(card => {
                    if (category === 'all' || card.getAttribute('data-category') === category) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>
</html>