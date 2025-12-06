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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $icon = $_POST['icon'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validation
    if (empty($name) || empty($description) || empty($price) || empty($category)) {
        $error = 'Please fill in all required fields.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } else {
        try {
            // Insert product
            $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, description, price, category, icon, featured, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $name, $description, $price, $category, $icon, $featured]);
            
            $product_id = $pdo->lastInsertId();
            $success = 'Product added successfully!';
            
            // Clear form
            $_POST = array();
        } catch (PDOException $e) {
            $error = 'Error adding product: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Kreava Seller</title>
    <style>
        /* ========== CSS UNTUK ADD PRODUCT PAGE - WARNA SERASI DENGAN BUYER ========== */
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

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-3px) rotate(2deg); }
            66% { transform: translateY(2px) rotate(-2deg); }
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

        /* User Dropdown */
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text) 0%, var(--primary) 50%, var(--creative) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Form Container */
        .form-container {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            box-shadow: var(--shadow), var(--glow);
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Form Sections */
        .form-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .form-section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--primary);
        }

        .form-section-title i {
            font-size: 1.2rem;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .form-label i {
            color: var(--primary);
            font-size: 1rem;
            width: 20px;
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 4px;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 1rem 1.5rem;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            transform: translateY(-2px);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }

        .form-select {
            cursor: pointer;
        }

        .char-count {
            text-align: right;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        /* Icon Selection */
        .icon-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .icon-option {
            text-align: center;
            cursor: pointer;
        }

        .icon-option input {
            display: none;
        }

        .icon-preview {
            width: 60px;
            height: 60px;
            background: var(--surface-light);
            border: 2px solid var(--glass-border);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 1.5rem;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .icon-option input:checked + .icon-preview {
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            border-color: var(--primary);
            transform: scale(1.1);
        }

        .icon-name {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .checkbox-group:hover {
            border-color: var(--primary);
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .checkbox input {
            width: 20px;
            height: 20px;
            accent-color: var(--primary);
        }

        .featured-badge {
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        /* Alerts */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .alert i {
            font-size: 1.3rem;
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

        /* Preview Section */
        .preview-section {
            margin-top: 3rem;
        }

        .preview-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 1rem;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .preview-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .preview-badge {
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .preview-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .preview-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .preview-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .preview-price {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn {
                width: 100%;
                justify-content: center;
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
            
            .container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 2rem;
            }
            
            .form-section {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem;
            }
            
            .form-section {
                padding: 1rem;
            }
            
            .form-input, .form-textarea, .form-select {
                padding: 0.875rem 1.25rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .icon-selection {
                grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
            }
            
            .icon-preview {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        /* ========== FOOTER STYLES ========== */
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

/* Responsive Footer */
@media (max-width: 968px) {
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
    .footer-bottom {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .footer-container {
        padding: 2rem 1.5rem 1.5rem;
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Add New Product</h1>
                <p class="page-subtitle">Create a new product listing to start selling on Kreava</p>
            </div>

            <!-- Alerts -->
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Product Form -->
            <form method="POST" class="form-container">
                <div class="form-grid">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h3>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-tag"></i>
                                Product Name
                            </label>
                            <input type="text" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   placeholder="Enter product name" required maxlength="100">
                            <div class="char-count">Max 100 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Describe your product in detail..." 
                                      required maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <div class="char-count">Max 500 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-dollar-sign"></i>
                                Price
                            </label>
                            <input type="number" name="price" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-cog"></i>
                            Product Details
                        </h3>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-folder"></i>
                                Category
                            </label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="ui-ux" <?php echo ($_POST['category'] ?? '') === 'ui-ux' ? 'selected' : ''; ?>>UI/UX Design</option>
                                <option value="graphic" <?php echo ($_POST['category'] ?? '') === 'graphic' ? 'selected' : ''; ?>>Graphic Design</option>
                                <option value="web" <?php echo ($_POST['category'] ?? '') === 'web' ? 'selected' : ''; ?>>Web Templates</option>
                                <option value="mobile" <?php echo ($_POST['category'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Mobile Assets</option>
                                <option value="illustration" <?php echo ($_POST['category'] ?? '') === 'illustration' ? 'selected' : ''; ?>>Illustrations</option>
                                <option value="font" <?php echo ($_POST['category'] ?? '') === 'font' ? 'selected' : ''; ?>>Fonts</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-icons"></i>
                                Icon
                            </label>
                            <div class="icon-selection">
                                <?php
                                $icons = [
                                    'palette' => 'Art',
                                    'mobile' => 'Mobile',
                                    'desktop' => 'Web',
                                    'paint-brush' => 'Design',
                                    'image' => 'Image',
                                    'font' => 'Font',
                                    'cube' => '3D',
                                    'video' => 'Video',
                                    'music' => 'Audio',
                                    'code' => 'Code'
                                ];
                                foreach ($icons as $icon => $name):
                                ?>
                                <label class="icon-option">
                                    <input type="radio" name="icon" value="<?php echo $icon; ?>" 
                                           <?php echo ($_POST['icon'] ?? 'palette') === $icon ? 'checked' : ''; ?> required>
                                    <div class="icon-preview">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="icon-name"><?php echo $name; ?></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="checkbox-group">
                            <label class="checkbox">
                                <input type="checkbox" name="featured" value="1" 
                                       <?php echo isset($_POST['featured']) ? 'checked' : ''; ?>>
                                <span>Feature this product</span>
                            </label>
                            <span class="featured-badge">Featured</span>
                        </div>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="preview-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-eye"></i>
                        Live Preview
                    </h3>
                    <div class="preview-card">
                        <div class="preview-header">
                            <div class="preview-icon">
                                <i class="fas fa-<?php echo $_POST['icon'] ?? 'palette'; ?>"></i>
                            </div>
                            <?php if(isset($_POST['featured'])): ?>
                            <div class="preview-badge">Featured</div>
                            <?php endif; ?>
                        </div>
                        <h3 class="preview-title"><?php echo htmlspecialchars($_POST['name'] ?? 'Product Name'); ?></h3>
                        <p class="preview-description"><?php echo htmlspecialchars($_POST['description'] ?? 'Product description will appear here...'); ?></p>
                        <div class="preview-footer">
                            <div class="preview-price">$<?php echo number_format($_POST['price'] ?? 0, 2); ?></div>
                            <div>
                                <span class="feature-tag"><?php echo ucfirst($_POST['category'] ?? 'category'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="btn">
                        <i class="fas fa-plus"></i>
                        Add Product
                    </button>
                </div>
            </form>
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
        // Live preview update
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('input, textarea, select');
            const previewElements = {
                name: document.querySelector('.preview-title'),
                description: document.querySelector('.preview-description'),
                price: document.querySelector('.preview-price'),
                category: document.querySelector('.preview-footer .feature-tag'),
                icon: document.querySelector('.preview-icon i'),
                featured: document.querySelector('.preview-badge')
            };

            formInputs.forEach(input => {
                input.addEventListener('input', updatePreview);
                input.addEventListener('change', updatePreview);
            });

            function updatePreview() {
                // Update name
                if (previewElements.name) {
                    previewElements.name.textContent = document.querySelector('input[name="name"]').value || 'Product Name';
                }

                // Update description
                if (previewElements.description) {
                    previewElements.description.textContent = document.querySelector('textarea[name="description"]').value || 'Product description will appear here...';
                }

                // Update price
                if (previewElements.price) {
                    const price = document.querySelector('input[name="price"]').value || '0';
                    previewElements.price.textContent = '$' + parseFloat(price).toFixed(2);
                }

                // Update category
                if (previewElements.category) {
                    const category = document.querySelector('select[name="category"]').value || 'category';
                    previewElements.category.textContent = category.charAt(0).toUpperCase() + category.slice(1);
                }

                // Update icon
                if (previewElements.icon) {
                    const selectedIcon = document.querySelector('input[name="icon"]:checked');
                    if (selectedIcon) {
                        previewElements.icon.className = 'fas fa-' + selectedIcon.value;
                    }
                }

                // Update featured badge
                if (previewElements.featured) {
                    const featured = document.querySelector('input[name="featured"]');
                    if (featured && featured.checked) {
                        previewElements.featured.style.display = 'block';
                    } else {
                        previewElements.featured.style.display = 'none';
                    }
                }
            }

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

            // Initialize preview
            updatePreview();
        });
    </script>
</body>
</html>