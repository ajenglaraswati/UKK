<?php
// HANYA SATU SESSION START - di sini saja
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika user sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user']) && isset($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'seller') {
        header('Location: seller/dashboard.php');
        exit();
    } else {
        header('Location: buyer/dashboard.php');
        exit();
    }
}

// Include config - PASTIKAN TIDAK ADA SESSION START DI DALAM CONFIG
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kreava - Creative Digital Assets</title>
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
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 50%, #d9f0ff 100%);
            color: #1e293b;
            line-height: 1.5;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* ===== FLOATING ICONS BACKGROUND - 30 IKON BETERBANGAN ===== */
        .floating-icons-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(4px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 35px -10px rgba(2, 132, 199, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #0284c7;
            animation: fly-around 22s infinite linear;
        }

        /* Variasi ukuran */
        .floating-icon.small { width: 45px; height: 45px; font-size: 1.4rem; }
        .floating-icon.medium { width: 60px; height: 60px; font-size: 1.8rem; }
        .floating-icon.large { width: 75px; height: 75px; font-size: 2.2rem; }
        .floating-icon.xlarge { width: 90px; height: 90px; font-size: 2.6rem; }

        /* Posisi dan delay berbeda untuk setiap ikon - 30 ikon */
        .floating-icon:nth-child(1) { top: 5%; left: 2%; animation-duration: 18s; animation-delay: 0s; }
        .floating-icon:nth-child(2) { top: 8%; right: 3%; animation-duration: 22s; animation-delay: 1s; }
        .floating-icon:nth-child(3) { top: 15%; left: 8%; animation-duration: 20s; animation-delay: 2s; }
        .floating-icon:nth-child(4) { top: 22%; right: 12%; animation-duration: 25s; animation-delay: 3s; }
        .floating-icon:nth-child(5) { top: 28%; left: 18%; animation-duration: 19s; animation-delay: 4s; }
        .floating-icon:nth-child(6) { top: 35%; right: 22%; animation-duration: 23s; animation-delay: 5s; }
        .floating-icon:nth-child(7) { top: 42%; left: 28%; animation-duration: 21s; animation-delay: 6s; }
        .floating-icon:nth-child(8) { top: 48%; right: 32%; animation-duration: 24s; animation-delay: 7s; }
        .floating-icon:nth-child(9) { top: 55%; left: 38%; animation-duration: 18s; animation-delay: 8s; }
        .floating-icon:nth-child(10) { top: 62%; right: 42%; animation-duration: 22s; animation-delay: 9s; }
        .floating-icon:nth-child(11) { top: 68%; left: 48%; animation-duration: 20s; animation-delay: 10s; }
        .floating-icon:nth-child(12) { top: 75%; right: 52%; animation-duration: 23s; animation-delay: 11s; }
        .floating-icon:nth-child(13) { top: 82%; left: 58%; animation-duration: 19s; animation-delay: 12s; }
        .floating-icon:nth-child(14) { top: 88%; right: 62%; animation-duration: 24s; animation-delay: 13s; }
        .floating-icon:nth-child(15) { top: 92%; left: 68%; animation-duration: 21s; animation-delay: 14s; }
        .floating-icon:nth-child(16) { top: 12%; left: 78%; animation-duration: 25s; animation-delay: 15s; }
        .floating-icon:nth-child(17) { top: 25%; right: 72%; animation-duration: 18s; animation-delay: 16s; }
        .floating-icon:nth-child(18) { top: 38%; left: 88%; animation-duration: 22s; animation-delay: 17s; }
        .floating-icon:nth-child(19) { top: 52%; right: 82%; animation-duration: 20s; animation-delay: 18s; }
        .floating-icon:nth-child(20) { top: 65%; left: 12%; animation-duration: 23s; animation-delay: 19s; }
        .floating-icon:nth-child(21) { top: 72%; right: 92%; animation-duration: 19s; animation-delay: 20s; }
        .floating-icon:nth-child(22) { top: 45%; left: 42%; animation-duration: 24s; animation-delay: 21s; }
        .floating-icon:nth-child(23) { top: 58%; right: 8%; animation-duration: 21s; animation-delay: 22s; }
        .floating-icon:nth-child(24) { top: 18%; left: 62%; animation-duration: 18s; animation-delay: 23s; }
        .floating-icon:nth-child(25) { top: 95%; right: 18%; animation-duration: 22s; animation-delay: 24s; }
        .floating-icon:nth-child(26) { top: 32%; left: 5%; animation-duration: 25s; animation-delay: 25s; }
        .floating-icon:nth-child(27) { top: 78%; right: 38%; animation-duration: 20s; animation-delay: 26s; }
        .floating-icon:nth-child(28) { top: 42%; left: 72%; animation-duration: 23s; animation-delay: 27s; }
        .floating-icon:nth-child(29) { top: 85%; right: 28%; animation-duration: 19s; animation-delay: 28s; }
        .floating-icon:nth-child(30) { top: 5%; left: 92%; animation-duration: 24s; animation-delay: 29s; }

        @keyframes fly-around {
            0% {
                transform: translate(0, 0) rotate(0deg) scale(1);
                opacity: 0.6;
            }
            25% {
                transform: translate(120px, -80px) rotate(90deg) scale(1.1);
                opacity: 0.9;
            }
            50% {
                transform: translate(-80px, 120px) rotate(180deg) scale(0.9);
                opacity: 0.6;
            }
            75% {
                transform: translate(60px, -60px) rotate(270deg) scale(1.1);
                opacity: 0.9;
            }
            100% {
                transform: translate(0, 0) rotate(360deg) scale(1);
                opacity: 0.6;
            }
        }

        /* Floating shapes background */
        .floating-shape {
            position: absolute;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            animation: pulse-shape 12s infinite ease-in-out;
        }

        .floating-shape:nth-child(31) { width: 500px; height: 500px; top: -150px; right: -100px; }
        .floating-shape:nth-child(32) { width: 600px; height: 600px; bottom: -200px; left: -150px; }
        .floating-shape:nth-child(33) { width: 400px; height: 400px; top: 40%; left: 20%; }

        @keyframes pulse-shape {
            0%, 100% { transform: scale(1); opacity: 0.2; }
            50% { transform: scale(1.4); opacity: 0.4; }
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
        }

        .logo i {
            font-size: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
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

        .nav-buttons {
            display: flex;
            gap: 1rem;
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

        /* Container */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 2;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 1rem 3rem;
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 50px;
            color: #0284c7;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            font-size: 1.2rem;
            color: #475569;
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-primary {
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-secondary {
            padding: 1rem 2rem;
            border: 1px solid #bae6fd;
            border-radius: 50px;
            background: white;
            color: #0369a1;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: #475569;
            font-size: 1.2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            padding: 2.5rem 2rem;
            transition: all 0.4s ease;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: white;
            border-color: #38bdf8;
            box-shadow: 0 30px 50px -20px rgba(2, 132, 199, 0.4);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .feature-icon i {
            font-size: 2rem;
            color: #0284c7;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #475569;
            line-height: 1.7;
        }

        /* Products Section */
        .products {
            padding: 5rem 0;
        }

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
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 50px -20px rgba(2, 132, 199, 0.3);
            border-color: #38bdf8;
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .product-image i {
            font-size: 4rem;
            color: #0284c7;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image i {
            transform: scale(1.1) rotate(5deg);
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .product-content {
            padding: 2rem;
        }

        .product-category {
            font-size: 0.8rem;
            color: #0284c7;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0284c7;
        }

        .product-btn {
            width: 45px;
            height: 45px;
            border-radius: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .product-btn:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            transform: scale(1.1);
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            border-radius: 40px;
            padding: 5rem;
            text-align: center;
            margin: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        }

        .cta h2 {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .cta p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 2.5rem;
            font-size: 1.2rem;
            position: relative;
        }

        .cta .btn-primary {
            background: white;
            color: #0284c7;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .cta .btn-secondary {
            background: transparent;
            border-color: white;
            color: white;
        }

        .cta .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }

        /* Footer */
        .footer {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(56, 189, 248, 0.2);
            padding: 4rem 0 2rem;
            margin-top: 4rem;
            position: relative;
            z-index: 2;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0284c7;
            margin-bottom: 1.5rem;
        }

        .footer-section p {
            color: #475569;
            line-height: 1.7;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
        }

        .footer-section ul li a {
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #0284c7;
            padding-left: 5px;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            width: 45px;
            height: 45px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            transform: translateY(-5px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 3rem;
            margin-top: 3rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .features-grid,
            .products-grid {
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
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                max-width: 300px;
                margin: 0 auto;
            }
            
            .features-grid,
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .cta {
                padding: 3rem 2rem;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-section {
                text-align: center;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .floating-icon {
                display: none; /* Sembunyikan di mobile */
            }
        }
    </style>
</head>
<body>
    <!-- ===== 30 IKON PRODUK DIGITAL BETERBANGAN ===== -->
    <div class="floating-icons-container">
        <!-- Ikon-ikon dengan berbagai ukuran (30 ikon) -->
        <div class="floating-icon xlarge"><i class="fas fa-palette"></i></div>
        <div class="floating-icon large"><i class="fas fa-cube"></i></div>
        <div class="floating-icon medium"><i class="fas fa-paint-brush"></i></div>
        <div class="floating-icon small"><i class="fas fa-mobile-alt"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-laptop-code"></i></div>
        <div class="floating-icon large"><i class="fas fa-camera"></i></div>
        <div class="floating-icon medium"><i class="fas fa-video"></i></div>
        <div class="floating-icon small"><i class="fas fa-music"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-book-open"></i></div>
        <div class="floating-icon large"><i class="fas fa-rocket"></i></div>
        <div class="floating-icon medium"><i class="fas fa-bolt"></i></div>
        <div class="floating-icon small"><i class="fas fa-star"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-magic"></i></div>
        <div class="floating-icon large"><i class="fas fa-pencil-ruler"></i></div>
        <div class="floating-icon medium"><i class="fas fa-layer-group"></i></div>
        <div class="floating-icon small"><i class="fas fa-vector-square"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-shapes"></i></div>
        <div class="floating-icon large"><i class="fas fa-draw-polygon"></i></div>
        <div class="floating-icon medium"><i class="fas fa-paint-roller"></i></div>
        <div class="floating-icon small"><i class="fas fa-robot"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-microchip"></i></div>
        <div class="floating-icon large"><i class="fas fa-crown"></i></div>
        <div class="floating-icon medium"><i class="fas fa-gem"></i></div>
        <div class="floating-icon small"><i class="fas fa-wand-sparkles"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-cloud-arrow-up"></i></div>
        <div class="floating-icon large"><i class="fas fa-code"></i></div>
        <div class="floating-icon medium"><i class="fas fa-database"></i></div>
        <div class="floating-icon small"><i class="fas fa-gamepad"></i></div>
        <div class="floating-icon xlarge"><i class="fas fa-headphones"></i></div>
        <div class="floating-icon large"><i class="fas fa-photo-film"></i></div>
        
        <!-- Floating Shapes -->
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
        <div class="floating-shape"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </div>
            
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
            </div>

            <div class="nav-buttons">
                <a href="login.php" class="btn-login">Login</a>
                <a href="register.php" class="btn-register">Register</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <!-- Hero Section -->
        <section class="hero">
            <span class="hero-badge">
                <i class="fas fa-bolt"></i>
                Creative Digital Assets
            </span>
            <h1>Unleash Your Creativity with Kreava</h1>
            <p>Discover premium digital products designed to inspire and elevate your creative projects.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-primary">
                    <i class="fas fa-rocket"></i>
                    Get Started Free
                </a>
                <a href="products.php" class="btn-secondary">
                    <i class="fas fa-play"></i>
                    Browse Products
                </a>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features">
            <div class="section-header">
                <h2>Why Choose Kreava?</h2>
                <p>Everything you need to bring your creative ideas to life</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Instant Downloads</h3>
                    <p>Get your files immediately after purchase. No waiting, no delays.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <h3>Lifetime Updates</h3>
                    <p>Free updates for all your purchases. Always get the latest versions.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Premium Support</h3>
                    <p>24/7 customer support to help you with any questions.</p>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section class="products">
            <div class="section-header">
                <h2>Popular Products</h2>
                <p>Check out our most loved digital assets</p>
            </div>

            <div class="products-grid">
                <?php
                // Query untuk menampilkan produk
                try {
                    $stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 LIMIT 3");
                    while ($product = $stmt->fetch()):
                    $icon = !empty($product['icon']) ? $product['icon'] : 'palette';
                ?>
                <div class="product-card">
                    <div class="product-image">
                        <div class="product-badge">FEATURED</div>
                        <i class="fas fa-<?php echo htmlspecialchars($icon); ?>"></i>
                    </div>
                    <div class="product-content">
                        <div class="product-category">DIGITAL ASSET</div>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-footer">
                            <span class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="product-btn">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    endwhile;
                } catch (PDOException $e) {
                    echo "<div style='grid-column: 1/-1; text-align: center; color: #ef4444;'>Error loading products</div>";
                }
                ?>
            </div>

            <div style="text-align: center;">
                <a href="products.php" class="btn-primary" style="padding: 1rem 3rem;">
                    <i class="fas fa-th-large"></i>
                    View All Products
                </a>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <h2>Ready to Start Your Creative Journey?</h2>
            <p>Join thousands of creators and buyers in our community.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Sign Up Free
                </a>
                <a href="login.php" class="btn-secondary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <i class="fas fa-palette"></i>
                    Kreava
                </div>
                <p>Empowering creativity through digital assets. Join our community of creators and innovators.</p>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Support</h4>
                <ul>
                    <li><a href="help.php">Help Center</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Connect</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 Kreava. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>