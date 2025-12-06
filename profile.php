<?php
session_start();
// Jika user belum login, redirect ke login page
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

include 'includes/config.php';

$user_id = $_SESSION['user']['id'];
$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $job = $_POST['job'] ?? '';
    $company = $_POST['company'] ?? '';

    try {
        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ?, location = ?, website = ?, job = ?, company = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $bio, $location, $website, $job, $company, $user_id]);
        
        // Update session data
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['bio'] = $bio;
        $_SESSION['user']['location'] = $location;
        $_SESSION['user']['website'] = $website;
        $_SESSION['user']['job'] = $job;
        $_SESSION['user']['company'] = $company;
        
        $success_msg = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error updating profile: " . $e->getMessage();
    }
}

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit();
}

// Initialize stats
$products_count = 0;
$sales_count = 0;
$avg_rating = 0;
$purchases_count = 0;
$wishlist_count = 0;
$reviews_count = 0;

// Get stats based on user role
if ($_SESSION['user']['role'] === 'seller') {
    try {
        // Products count
        $table_check = $pdo->query("SHOW TABLES LIKE 'products'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('seller_id', $columns)) {
                $products_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
                $products_stmt->execute([$user_id]);
                $products_count = $products_stmt->fetch()['count'];
            } elseif (in_array('user_id', $columns)) {
                $products_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE user_id = ?");
                $products_stmt->execute([$user_id]);
                $products_count = $products_stmt->fetch()['count'];
            }
        }
        
        // Sales count
        $table_check = $pdo->query("SHOW TABLES LIKE 'orders'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('seller_id', $columns)) {
                $sales_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ? AND status = 'completed'");
                $sales_stmt->execute([$user_id]);
                $sales_count = $sales_stmt->fetch()['count'];
            }
        }
        
        // Average rating
        $table_check = $pdo->query("SHOW TABLES LIKE 'reviews'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM reviews")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('seller_id', $columns)) {
                $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE seller_id = ?");
                $rating_stmt->execute([$user_id]);
                $rating_result = $rating_stmt->fetch();
                $avg_rating = $rating_result['avg_rating'] ? round($rating_result['avg_rating'], 1) : 0;
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching seller stats: " . $e->getMessage());
    }
} else {
    try {
        // Buyer stats
        $table_check = $pdo->query("SHOW TABLES LIKE 'orders'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('user_id', $columns)) {
                $purchases_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
                $purchases_stmt->execute([$user_id]);
                $purchases_count = $purchases_stmt->fetch()['count'];
            }
        }
        
        // Wishlist count
        $table_check = $pdo->query("SHOW TABLES LIKE 'wishlist'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM wishlist")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('user_id', $columns)) {
                $wishlist_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
                $wishlist_stmt->execute([$user_id]);
                $wishlist_count = $wishlist_stmt->fetch()['count'];
            }
        }
        
        // Reviews count
        $table_check = $pdo->query("SHOW TABLES LIKE 'reviews'")->fetch();
        if ($table_check) {
            $columns = $pdo->query("SHOW COLUMNS FROM reviews")->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('user_id', $columns)) {
                $reviews_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
                $reviews_stmt->execute([$user_id]);
                $reviews_count = $reviews_stmt->fetch()['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching buyer stats: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Kreava</title>
    <style>
        /* ========== CSS VARIABLES SAMA DENGAN DASHBOARD ========== */
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
            --error: #ef4444;
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

        /* Navigation - Sama dengan Dashboard */
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

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-3px) rotate(2deg); }
            66% { transform: translateY(2px) rotate(-2deg); }
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

        .user-dropdown .dropdown-divider {
            height: 1px;
            background: var(--glass-border);
            margin: 6px 0;
        }

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

        /* Profile Header */
        .profile-header {
            margin-bottom: 3rem;
        }

        .profile-hero {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 3rem;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .profile-avatar {
            text-align: center;
        }

        .avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
            border: 4px solid var(--glass);
            box-shadow: var(--shadow);
        }

        .avatar-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .profile-role {
            margin-bottom: 1rem;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--creative));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .profile-bio {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 2rem;
            max-width: 400px;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: var(--surface-light);
            border-radius: 15px;
            border: 1px solid var(--glass-border);
        }

        .stat-value {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Edit Profile Section */
        .edit-profile-section {
            margin-bottom: 3rem;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            backdrop-filter: blur(20px);
            border: 1px solid transparent;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        /* Profile Form */
        .profile-form-container {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .profile-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--creative));
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .form-section {
            background: var(--surface-light);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
        }

        .form-section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-family: 'Poppins', sans-serif;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .char-count {
            text-align: right;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
        }

        /* Buttons */
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

        /* Security Section */
        .security-section {
            margin-bottom: 4rem;
        }

        .security-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .security-card {
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

        .security-card::before {
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

        .security-card:hover::before {
            transform: scaleX(1);
        }

        .security-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow), var(--glow);
            border-color: var(--primary);
        }

        .security-icon {
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

        .security-content {
            flex: 1;
        }

        .security-content h4 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .security-content p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 0;
        }

        .security-arrow {
            color: var(--text-secondary);
            transition: transform 0.3s ease;
        }

        .security-card:hover .security-arrow {
            transform: translateX(5px);
            color: var(--primary);
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

        /* Responsive Design */
        @media (max-width: 968px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .profile-hero {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }
            
            .profile-stats {
                margin: 0 auto;
            }
            
            .security-actions {
                grid-template-columns: 1fr;
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
            
            .user-name {
                display: none;
            }
            
            .user-profile {
                padding: 8px;
            }
            
            .profile-hero {
                padding: 2rem;
            }
            
            .profile-name {
                font-size: 2rem;
            }
            
            .profile-form-container {
                padding: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
            
            .profile-hero {
                padding: 1.5rem;
            }
            
            .profile-name {
                font-size: 1.8rem;
            }
            
            .profile-form-container {
                padding: 1.5rem;
            }
            
            .form-section {
                padding: 1.5rem;
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
                <a href="index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="logo-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <span class="logo-text">Kreava</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-cube"></i>
                    <span>Products</span>
                </a>
                <?php if ($_SESSION['user']['role'] === 'seller'): ?>
                    <a href="seller/dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="buyer/dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-auth">
                <div class="user-dropdown">
                    <button class="user-profile">
                        <div class="avatar">
                            <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                <img src="assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user" style="color: white; font-size: 14px;"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user-edit"></i>
                            Edit Profile
                        </a>
                        <?php if ($_SESSION['user']['role'] === 'seller'): ?>
                            <a href="seller/dashboard.php" class="dropdown-item">
                                <i class="fas fa-store"></i>
                                Seller Dashboard
                            </a>
                        <?php else: ?>
                            <a href="buyer/dashboard.php" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                Buyer Dashboard
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
                
                <button class="theme-toggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="container">
            <!-- Profile Header -->
            <section class="profile-header">
                <div class="profile-hero">
                    <div class="profile-avatar">
                        <div class="avatar-large">
                            <?php if(isset($user['avatar']) && !empty($user['avatar'])): ?>
                                <img src="assets/images/<?php echo $user['avatar']; ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-actions">
                            <button class="btn btn-small btn-secondary">
                                <i class="fas fa-camera"></i>
                                Change Photo
                            </button>
                        </div>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p class="profile-role">
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <i class="fas fa-<?php echo $user['role'] === 'seller' ? 'store' : 'shopping-bag'; ?>"></i>
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </p>
                        <p class="profile-bio"><?php echo htmlspecialchars($user['bio'] ?? 'No bio yet'); ?></p>
                        <div class="profile-stats">
                            <?php if ($_SESSION['user']['role'] === 'seller'): ?>
                                <!-- Stats untuk Seller -->
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $products_count; ?></div>
                                    <div class="stat-label">Products</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $sales_count; ?></div>
                                    <div class="stat-label">Sales</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $avg_rating; ?></div>
                                    <div class="stat-label">Rating</div>
                                </div>
                            <?php else: ?>
                                <!-- Stats untuk Buyer -->
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $purchases_count; ?></div>
                                    <div class="stat-label">Purchases</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $wishlist_count; ?></div>
                                    <div class="stat-label">Wishlist</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $reviews_count; ?></div>
                                    <div class="stat-label">Reviews</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Edit Profile Form -->
            <section class="edit-profile-section">
                <div class="section-header">
                    <h2 class="section-title">Edit Profile</h2>
                    <p class="section-subtitle">Update your personal information and preferences</p>
                </div>

                <!-- Messages -->
                <?php if ($success_msg): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_msg): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-form-container">
                    <form method="POST" class="profile-form">
                        <div class="form-grid">
                            <!-- Personal Information -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <i class="fas fa-user-circle"></i>
                                    Personal Information
                                </h3>
                                
                                <div class="form-group">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-signature"></i>
                                        Full Name
                                    </label>
                                    <input type="text" id="name" name="name" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Email Address
                                    </label>
                                    <input type="email" id="email" name="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Phone Number
                                    </label>
                                    <input type="tel" id="phone" name="phone" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="bio" class="form-label">
                                        <i class="fas fa-feather"></i>
                                        Bio
                                    </label>
                                    <textarea id="bio" name="bio" class="form-textarea" 
                                              placeholder="Tell us about yourself..." 
                                              maxlength="150"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    <div class="char-count">
                                        <span id="bio-counter"><?php echo strlen($user['bio'] ?? ''); ?></span>/150 characters
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Information -->
                            <div class="form-section">
                                <h3 class="form-section-title">
                                    <i class="fas fa-briefcase"></i>
                                    Professional Information
                                </h3>

                                <div class="form-group">
                                    <label for="job" class="form-label">
                                        <i class="fas fa-user-tie"></i>
                                        Job Title
                                    </label>
                                    <input type="text" id="job" name="job" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['job'] ?? ''); ?>" 
                                           placeholder="e.g. Graphic Designer">
                                </div>

                                <div class="form-group">
                                    <label for="company" class="form-label">
                                        <i class="fas fa-building"></i>
                                        Company
                                    </label>
                                    <input type="text" id="company" name="company" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['company'] ?? ''); ?>" 
                                           placeholder="e.g. Creative Studio Inc.">
                                </div>

                                <div class="form-group">
                                    <label for="location" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Location
                                    </label>
                                    <input type="text" id="location" name="location" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" 
                                           placeholder="e.g. Jakarta, Indonesia">
                                </div>

                                <div class="form-group">
                                    <label for="website" class="form-label">
                                        <i class="fas fa-globe"></i>
                                        Website
                                    </label>
                                    <input type="url" id="website" name="website" class="form-input" 
                                           value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                           placeholder="https://example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                <i class="fas fa-arrow-left"></i>
                                Cancel
                            </button>
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Security Section -->
            <section class="security-section">
                <div class="section-header">
                    <h2 class="section-title">Security Settings</h2>
                    <p class="section-subtitle">Manage your account security and preferences</p>
                </div>

                <div class="security-actions">
                    <a href="change-password.php" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="security-content">
                            <h4>Change Password</h4>
                            <p>Update your password regularly to keep your account secure</p>
                        </div>
                        <div class="security-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <a href="privacy-settings.php" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="security-content">
                            <h4>Privacy Settings</h4>
                            <p>Control who can see your profile and activity</p>
                        </div>
                        <div class="security-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <a href="notifications.php" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="security-content">
                            <h4>Notification Preferences</h4>
                            <p>Manage how you receive notifications</p>
                        </div>
                        <div class="security-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
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
                        <a href="products.php?category=ui-ux" class="footer-link">UI/UX Design</a>
                        <a href="products.php?category=graphic" class="footer-link">Graphic Design</a>
                        <a href="products.php?category=web" class="footer-link">Web Templates</a>
                        <a href="products.php?category=mobile" class="footer-link">Mobile Assets</a>
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

    <script>
        // Character counter for bio
        const bioTextarea = document.getElementById('bio');
        const bioCounter = document.getElementById('bio-counter');
        
        if (bioTextarea && bioCounter) {
            bioTextarea.addEventListener('input', function() {
                bioCounter.textContent = this.value.length;
            });
        }

        // Form validation
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!name) {
                e.preventDefault();
                alert('Please enter your full name');
                return;
            }
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address');
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });

        // Theme toggle functionality
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('light-theme');
                const icon = this.querySelector('i');
                if (document.body.classList.contains('light-theme')) {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
                
                localStorage.setItem('theme', document.body.classList.contains('light-theme') ? 'light' : 'dark');
            });
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                if (themeToggle) {
                    themeToggle.className = 'fas fa-sun';
                }
            }
        });
    </script>
</body>
</html>