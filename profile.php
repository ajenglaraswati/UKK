<?php
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/config.php';
include 'includes/functions.php';

// Check authentication
checkAuth();

$user_id = $_SESSION['user']['id'];
$success_msg = '';
$error_msg = '';

// Handle avatar upload
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'assets/images/';
    
    // Buat folder jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($_FILES['avatar']['name']);
    $target_file = $upload_dir . $file_name;
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($_FILES['avatar']['tmp_name']);
    if ($check === false) {
        $error_msg = "File is not an image.";
    } elseif ($_FILES['avatar']['size'] > 5000000) { // 5MB max
        $error_msg = "File is too large. Max size 5MB.";
    } elseif (!in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
        $error_msg = "Only JPG, JPEG, PNG & GIF files are allowed.";
    } else {
        // Hapus avatar lama jika ada
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $old_avatar = $stmt->fetchColumn();
        
        if ($old_avatar && file_exists($upload_dir . $old_avatar) && $old_avatar != 'default-avatar.png') {
            unlink($upload_dir . $old_avatar);
        }
        
        // Upload file baru
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$file_name, $user_id]);
            
            $_SESSION['user']['avatar'] = $file_name;
            $success_msg = "Avatar updated successfully!";
        } else {
            $error_msg = "Failed to upload avatar.";
        }
    }
}

// Handle form submission untuk update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['avatar'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $location = $_POST['location'] ?? '';
    $website = $_POST['website'] ?? '';
    $job = $_POST['job'] ?? '';
    $company = $_POST['company'] ?? '';

    // Validasi input
    if (empty($name) || empty($email)) {
        $error_msg = "Name and email are required!";
    } else {
        // Validasi email unik kecuali email sendiri
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $error_msg = "Email already used by another account!";
        } else {
            try {
                // Update user data
                $stmt = $pdo->prepare("
                    UPDATE users SET 
                        name = ?, 
                        email = ?, 
                        phone = ?, 
                        bio = ?, 
                        location = ?, 
                        website = ?, 
                        job = ?, 
                        company = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $bio, $location, $website, $job, $company, $user_id]);
                
                // Update session data
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                
                $success_msg = "Profile updated successfully!";
            } catch (PDOException $e) {
                $error_msg = "Error updating profile: " . $e->getMessage();
            }
        }
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

// Initialize stats dengan penanganan error
$products_count = 0;
$sales_count = 0;
$avg_rating = 0;
$purchases_count = 0;
$wishlist_count = 0;
$reviews_count = 0;
$followers_count = 0;
$following_count = 0;

// Get stats based on user role with error handling
if ($_SESSION['user']['role'] === 'seller') {
    try {
        // Cek apakah kolom seller_id ada
        $columns = $pdo->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('seller_id', $columns)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
            $stmt->execute([$user_id]);
            $products_count = $stmt->fetchColumn() ?: 0;
        }
    } catch (PDOException $e) {
        error_log("Error fetching products count: " . $e->getMessage());
        $products_count = 0;
    }
    
    try {
        // Cek apakah tabel orders dan order_items ada
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT o.id) 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN products p ON oi.product_id = p.id 
            WHERE p.seller_id = ? AND o.status = 'completed'
        ");
        $stmt->execute([$user_id]);
        $sales_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching sales count: " . $e->getMessage());
        $sales_count = 0;
    }
    
    try {
        // Cek apakah tabel reviews ada
        $stmt = $pdo->prepare("
            SELECT AVG(r.rating) 
            FROM reviews r 
            JOIN products p ON r.product_id = p.id 
            WHERE p.seller_id = ?
        ");
        $stmt->execute([$user_id]);
        $avg_rating = round($stmt->fetchColumn() ?: 0, 1);
    } catch (PDOException $e) {
        error_log("Error fetching average rating: " . $e->getMessage());
        $avg_rating = 0;
    }
    
    try {
        // Cek apakah tabel follows ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
        $stmt->execute([$user_id]);
        $followers_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching followers count: " . $e->getMessage());
        $followers_count = 0;
    }
} else {
    try {
        // Purchases count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$user_id]);
        $purchases_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching purchases count: " . $e->getMessage());
        $purchases_count = 0;
    }
    
    try {
        // Wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wishlist_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching wishlist count: " . $e->getMessage());
        $wishlist_count = 0;
    }
    
    try {
        // Reviews count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reviews_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching reviews count: " . $e->getMessage());
        $reviews_count = 0;
    }
    
    try {
        // Following count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
        $stmt->execute([$user_id]);
        $following_count = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {
        error_log("Error fetching following count: " . $e->getMessage());
        $following_count = 0;
    }
}

// Get member since
$member_since = date('F Y', strtotime($user['created_at']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Kreava</title>
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
            max-width: 1200px;
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

        /* Auth Buttons */
        .btn-login, .btn-register {
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-login {
            border: 1px solid #bae6fd;
            background: transparent;
            color: #0369a1;
        }

        .btn-login:hover {
            background: #f0f9ff;
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .btn-register {
            border: none;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        /* Main Content */
        main {
            flex: 1;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .profile-avatar {
            text-align: center;
        }

        .avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #0284c7;
            font-size: 3rem;
            border: 3px solid white;
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.3);
            overflow: hidden;
        }

        .avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .change-avatar-btn {
            background: white;
            border: 1px solid #0284c7;
            color: #0284c7;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .change-avatar-btn:hover {
            background: #0284c7;
            color: white;
        }

        .profile-info {
            flex: 1;
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #e0f2fe;
            color: #0284c7;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .profile-bio {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            max-width: 600px;
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .meta-item i {
            color: #0284c7;
        }

        .meta-item a {
            color: #0284c7;
            text-decoration: none;
        }

        .meta-item a:hover {
            text-decoration: underline;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
            border-color: #0284c7;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0284c7;
            margin-bottom: 0.2rem;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Form Section */
        .form-section {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: #0284c7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-label i {
            color: #0284c7;
            margin-right: 0.5rem;
        }

        .form-input, .form-textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .char-count {
            text-align: right;
            color: #94a3b8;
            font-size: 0.8rem;
            margin-top: 0.3rem;
        }

        /* Avatar Upload Form */
        .avatar-upload-form {
            display: inline;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

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
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #0284c7;
            color: #0284c7;
            background: #f0f9ff;
        }

        /* Security Section */
        .security-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .security-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .security-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -10px rgba(2, 132, 199, 0.2);
            border-color: #0284c7;
        }

        .security-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.3rem;
        }

        .security-content h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .security-content p {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Footer */
        .footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 2rem 0 1rem;
            margin-top: 3rem;
        }

        .footer-container {
            max-width: 1200px;
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
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 0.8rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-meta {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .security-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
            <a href="index.php" class="logo">
                <i class="fas fa-palette"></i>
                Kreava
            </a>
            
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <?php if ($_SESSION['user']['role'] === 'seller'): ?>
                    <a href="seller/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="buyer/dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="profile.php" class="active">Profile</a>
            </div>

            <div class="nav-buttons">
                <div class="user-dropdown">
                    <div class="user-profile">
                        <div class="avatar">
                            <?php if(isset($user['avatar']) && !empty($user['avatar']) && file_exists('assets/images/' . $user['avatar'])): ?>
                                <img src="assets/images/<?php echo $user['avatar']; ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                    </div>
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
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
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

            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-large">
                        <?php if(isset($user['avatar']) && !empty($user['avatar']) && file_exists('assets/images/' . $user['avatar'])): ?>
                            <img src="assets/images/<?php echo $user['avatar']; ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Form Upload Avatar -->
                    <form method="POST" enctype="multipart/form-data" class="avatar-upload-form">
                        <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display: none;">
                        <button type="button" class="change-avatar-btn" onclick="document.getElementById('avatar-input').click()">
                            <i class="fas fa-camera"></i>
                            Change Photo
                        </button>
                    </form>
                </div>
                
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <div class="profile-role">
                        <i class="fas fa-<?php echo $user['role'] === 'seller' ? 'store' : 'shopping-bag'; ?>"></i>
                        <?php echo ucfirst($user['role']); ?>
                    </div>
                    
                    <?php if (!empty($user['bio'])): ?>
                        <p class="profile-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php endif; ?>
                    
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?php echo $member_since; ?>
                        </div>
                        
                        <?php if (!empty($user['location'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($user['location']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-globe"></i>
                            <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank">
                                <?php echo parse_url($user['website'], PHP_URL_HOST); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['job']) && !empty($user['company'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-briefcase"></i>
                            <?php echo htmlspecialchars($user['job']); ?> at <?php echo htmlspecialchars($user['company']); ?>
                        </div>
                        <?php elseif (!empty($user['job'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($user['job']); ?>
                        </div>
                        <?php elseif (!empty($user['company'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-building"></i>
                            <?php echo htmlspecialchars($user['company']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php if ($_SESSION['user']['role'] === 'seller'): ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $products_count; ?></div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $sales_count; ?></div>
                        <div class="stat-label">Sales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $avg_rating; ?></div>
                        <div class="stat-label">Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $followers_count; ?></div>
                        <div class="stat-label">Followers</div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $purchases_count; ?></div>
                        <div class="stat-label">Purchases</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $wishlist_count; ?></div>
                        <div class="stat-label">Wishlist</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $reviews_count; ?></div>
                        <div class="stat-label">Reviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $following_count; ?></div>
                        <div class="stat-label">Following</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Profile Form -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-user-edit"></i>
                    Edit Profile
                </h2>

                <form method="POST">
                    <div class="form-grid">
                        <!-- Personal Information -->
                        <div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #0f172a;">Personal Information</h3>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-signature"></i>
                                    Full Name
                                </label>
                                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email Address
                                </label>
                                <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+62 xxx-xxxx-xxxx">
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">
                                    <i class="fas fa-feather"></i>
                                    Bio
                                </label>
                                <textarea name="bio" class="form-textarea" placeholder="Tell us about yourself..." maxlength="150"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="char-count">
                                    <span id="bio-counter"><?php echo strlen($user['bio'] ?? ''); ?></span>/150 characters
                                </div>
                            </div>
                        </div>

                        <!-- Professional Information -->
                        <div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: #0f172a;">Professional Information</h3>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tie"></i>
                                    Job Title
                                </label>
                                <input type="text" name="job" class="form-input" value="<?php echo htmlspecialchars($user['job'] ?? ''); ?>" placeholder="e.g. Graphic Designer">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-building"></i>
                                    Company
                                </label>
                                <input type="text" name="company" class="form-input" value="<?php echo htmlspecialchars($user['company'] ?? ''); ?>" placeholder="e.g. Creative Studio">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Location
                                </label>
                                <input type="text" name="location" class="form-input" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>" placeholder="e.g. Jakarta, Indonesia">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-globe"></i>
                                    Website
                                </label>
                                <input type="url" name="website" class="form-input" value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" placeholder="https://example.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </h2>

                <div class="security-grid">
                    <a href="change-password.php" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="security-content">
                            <h4>Change Password</h4>
                            <p>Update your password regularly</p>
                        </div>
                    </a>

                    <a href="#" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="security-content">
                            <h4>Privacy Settings</h4>
                            <p>Control who sees your info</p>
                        </div>
                    </a>

                    <a href="#" class="security-card">
                        <div class="security-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="security-content">
                            <h4>Notifications</h4>
                            <p>Manage your preferences</p>
                        </div>
                    </a>
                </div>
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
                        <li><a href="products.php?category=ui-ux">UI/UX Design</a></li>
                        <li><a href="products.php?category=graphic">Graphic Design</a></li>
                        <li><a href="products.php?category=web">Web Templates</a></li>
                        <li><a href="products.php?category=mobile">Mobile Assets</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="careers.php">Careers</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="terms.php">Terms</a></li>
                        <li><a href="privacy.php">Privacy</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Kreava. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Character counter for bio
        const bioTextarea = document.querySelector('textarea[name="bio"]');
        const bioCounter = document.getElementById('bio-counter');
        
        if (bioTextarea && bioCounter) {
            bioTextarea.addEventListener('input', function() {
                bioCounter.textContent = this.value.length;
            });
        }

        // Avatar upload - submit form when file selected
        const avatarInput = document.getElementById('avatar-input');
        if (avatarInput) {
            avatarInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Preview image before upload
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatarLarge = document.querySelector('.avatar-large');
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100%';
                        img.style.height = '100%';
                        img.style.objectFit = 'cover';
                        
                        // Replace icon or existing img
                        if (avatarLarge.querySelector('i')) {
                            avatarLarge.innerHTML = '';
                            avatarLarge.appendChild(img);
                        } else if (avatarLarge.querySelector('img')) {
                            avatarLarge.querySelector('img').src = e.target.result;
                        } else {
                            avatarLarge.appendChild(img);
                        }
                    }
                    reader.readAsDataURL(this.files[0]);
                    
                    // Submit form automatically
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>