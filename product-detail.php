<?php 
// Cek session terlebih dahulu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/config.php';
include 'includes/functions.php';

$product_id = $_GET['id'] ?? 0;

// Get product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get seller information
$stmt = $pdo->prepare("SELECT id, name, email, avatar, bio, location, job, company, created_at FROM users WHERE id = ?");
$stmt->execute([$product['seller_id']]);
$seller = $stmt->fetch();

if (!$seller) {
    // Fallback jika seller tidak ditemukan
    $seller = [
        'id' => 0,
        'name' => 'Unknown Seller',
        'avatar' => 'default-avatar.png',
        'bio' => '',
        'location' => '',
        'job' => '',
        'company' => '',
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Get seller stats
try {
    // Total products from this seller
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $stmt->execute([$seller['id']]);
    $seller_products_count = $stmt->fetchColumn();
    
    // Average rating for this seller's products
    $stmt = $pdo->prepare("
        SELECT AVG(r.rating) as avg_rating 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        WHERE p.seller_id = ?
    ");
    $stmt->execute([$seller['id']]);
    $seller_rating = round($stmt->fetchColumn() ?: 0, 1);
    
    // Total sales for this seller
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT o.id) as total_sales 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE oi.product_id IN (SELECT id FROM products WHERE seller_id = ?) 
        AND o.status = 'completed'
    ");
    $stmt->execute([$seller['id']]);
    $seller_sales = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $seller_products_count = 0;
    $seller_rating = 0;
    $seller_sales = 0;
}

// Get product reviews
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name, u.avatar 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.product_id = ? 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}

// Calculate average rating
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum_rating = 0;
    foreach ($reviews as $review) {
        $sum_rating += $review['rating'];
    }
    $avg_rating = round($sum_rating / $total_reviews, 1);
}

// Check if current user has purchased this product
$has_purchased = false;
$user_review = null;

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'buyer') {
    $user_id = $_SESSION['user']['id'];
    
    try {
        // Cek apakah user sudah membeli produk ini
        $stmt = $pdo->prepare("
            SELECT o.id 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
            LIMIT 1
        ");
        $stmt->execute([$user_id, $product_id]);
        $has_purchased = $stmt->fetch() ? true : false;
        
        // Cek apakah user sudah pernah memberikan review
        if ($has_purchased) {
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $user_review = $stmt->fetch();
        }
        
    } catch (PDOException $e) {
        // Jika tabel belum ada, set default
        $has_purchased = false;
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
        $_SESSION['error_message'] = "You must be logged in as a buyer to submit a review.";
        header("Location: product-detail.php?id=$product_id");
        exit();
    }
    
    $user_id = $_SESSION['user']['id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error_message'] = "Please select a valid rating.";
    } elseif (empty($comment)) {
        $_SESSION['error_message'] = "Please write a comment.";
    } elseif (strlen($comment) < 10) {
        $_SESSION['error_message'] = "Comment must be at least 10 characters.";
    } else {
        try {
            // Cek apakah user sudah membeli produk
            $stmt = $pdo->prepare("
                SELECT o.id 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
                LIMIT 1
            ");
            $stmt->execute([$user_id, $product_id]);
            
            if (!$stmt->fetch()) {
                $_SESSION['error_message'] = "You can only review products you have purchased.";
            } else {
                // Cek apakah user sudah pernah review
                $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                
                if ($stmt->fetch()) {
                    // Update existing review
                    $stmt = $pdo->prepare("UPDATE reviews SET rating = ?, comment = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$rating, $comment, $user_id, $product_id]);
                    $_SESSION['success_message'] = "Your review has been updated!";
                } else {
                    // Insert new review
                    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $product_id, $rating, $comment]);
                    $_SESSION['success_message'] = "Thank you for your review!";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error submitting review: " . $e->getMessage();
        }
    }
    
    header("Location: product-detail.php?id=$product_id");
    exit();
}

// Get messages from session
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Kreava</title>
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

        .btn-dashboard {
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: 40px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
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

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #0284c7;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Product Detail */
        .product-detail {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        .product-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        /* Image Section */
        .product-image {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 24px;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-image .default-icon {
            font-size: 6rem;
            color: #0284c7;
        }

        .product-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Info Section */
        .product-category {
            display: inline-block;
            padding: 0.4rem 1rem;
            background: #e0f2fe;
            border-radius: 50px;
            color: #0284c7;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .product-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stars {
            display: flex;
            gap: 0.25rem;
            color: #fbbf24;
        }

        .stars .empty {
            color: #e2e8f0;
        }

        .rating-value {
            font-weight: 600;
            color: #0f172a;
        }

        .reviews-count {
            color: #64748b;
            font-size: 0.9rem;
        }

        .product-description {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        /* Features */
        .product-features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            padding: 1.5rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .feature {
            text-align: center;
        }

        .feature i {
            font-size: 1.5rem;
            color: #0284c7;
            margin-bottom: 0.5rem;
        }

        .feature span {
            display: block;
            font-weight: 600;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .feature small {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Seller Info */
        .seller-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 16px;
            margin: 1.5rem 0;
        }

        .seller-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.8rem;
            overflow: hidden;
        }

        .seller-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .seller-info {
            flex: 1;
        }

        .seller-info h4 {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .seller-title {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .seller-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .seller-meta i {
            color: #0284c7;
            margin-right: 0.25rem;
        }

        .seller-bio {
            margin-top: 0.5rem;
            color: #475569;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* Price */
        .price-section {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }

        .current-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0284c7;
        }

        .price-label {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
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
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 132, 199, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            flex: 1;
            background: white;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-outline:hover {
            border-color: #0284c7;
            color: #0284c7;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1rem;
        }

        /* Additional Info */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            color: #64748b;
            font-size: 0.85rem;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
        }

        /* Review Form */
        .review-form-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .review-form-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .rating-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .rating-star {
            font-size: 1.8rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .rating-star:hover,
        .rating-star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }

        .rating-star.selected {
            color: #fbbf24;
        }

        .review-textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.95rem;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 1rem;
        }

        .review-textarea:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .char-count {
            text-align: right;
            color: #94a3b8;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .purchase-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Reviews Section */
        .reviews-section {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .reviews-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0f172a;
        }

        .reviews-summary {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .average-rating {
            font-size: 2rem;
            font-weight: 700;
            color: #0284c7;
        }

        .review-card {
            padding: 1.5rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .review-card:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .reviewer {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }

        .reviewer-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.2rem;
            overflow: hidden;
        }

        .reviewer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reviewer-info h4 {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .reviewer-info .date {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
            color: #fbbf24;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .review-text {
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .user-review-badge {
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.2rem 0.6rem;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
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
            .product-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
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

            .product-features {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .reviews-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
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
                <?php if(isset($_SESSION['user'])): ?>
                    <?php if($_SESSION['user']['role'] === 'seller'): ?>
                        <a href="seller/dashboard.php">Dashboard</a>
                    <?php else: ?>
                        <a href="buyer/dashboard.php">Dashboard</a>
                        <a href="wishlist.php">Wishlist</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="nav-buttons">
                <?php if(isset($_SESSION['user'])): ?>
                    <div class="user-dropdown">
                        <div class="user-profile">
                            <div class="avatar">
                                <?php if(isset($_SESSION['user']['avatar']) && !empty($_SESSION['user']['avatar'])): ?>
                                    <img src="assets/images/<?php echo $_SESSION['user']['avatar']; ?>" alt="Profile">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                            <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #94a3b8;"></i>
                        </div>
                        <div class="dropdown-menu">
                            <?php if($_SESSION['user']['role'] === 'seller'): ?>
                                <a href="seller/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-store"></i>
                                    Dashboard
                                </a>
                                <a href="seller/manage-products.php" class="dropdown-item">
                                    <i class="fas fa-cube"></i>
                                    My Products
                                </a>
                            <?php else: ?>
                                <a href="buyer/dashboard.php" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="buyer/orders.php" class="dropdown-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    My Orders
                                </a>
                                <a href="wishlist.php" class="dropdown-item">
                                    <i class="fas fa-heart"></i>
                                    Wishlist
                                </a>
                            <?php endif; ?>
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="products.php">Products</a>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>

            <!-- Product Detail -->
            <div class="product-detail">
                <div class="product-grid">
                    <!-- Image -->
                    <div class="product-image">
                        <?php if($product['featured']): ?>
                        <div class="product-badge">Featured</div>
                        <?php endif; ?>
                        
                        <?php 
                        $product_image = !empty($product['image']) ? 'uploads/products/' . $product['image'] : null;
                        if ($product_image && file_exists($product_image)): ?>
                            <img src="<?php echo $product_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?> default-icon"></i>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div>
                        <span class="product-category">
                            <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                            <?php echo ucfirst($product['category'] ?? 'Digital Asset'); ?>
                        </span>

                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                        <!-- Rating -->
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++):
                                    if($i <= floor($avg_rating)):
                                ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif($i == ceil($avg_rating) && $avg_rating != floor($avg_rating)): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; 
                                endfor; ?>
                            </div>
                            <span class="rating-value"><?php echo $avg_rating; ?></span>
                            <span class="reviews-count">(<?php echo $total_reviews; ?> reviews)</span>
                        </div>

                        <!-- Description -->
                        <p class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </p>

                        <!-- Features -->
                        <div class="product-features">
                            <div class="feature">
                                <i class="fas fa-download"></i>
                                <span>Instant Download</span>
                                <small>Get files immediately</small>
                            </div>
                            <div class="feature">
                                <i class="fas fa-sync"></i>
                                <span>Lifetime Updates</span>
                                <small>Free forever</small>
                            </div>
                            <div class="feature">
                                <i class="fas fa-headset"></i>
                                <span>24/7 Support</span>
                                <small>Premium assistance</small>
                            </div>
                        </div>

                        <!-- Seller Info (LENGKAP DENGAN DATA DARI DATABASE) -->
                        <div class="seller-card">
                            <div class="seller-avatar">
                                <?php if (!empty($seller['avatar']) && file_exists('assets/images/' . $seller['avatar'])): ?>
                                    <img src="assets/images/<?php echo $seller['avatar']; ?>" alt="<?php echo $seller['name']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-store"></i>
                                <?php endif; ?>
                            </div>
                            <div class="seller-info">
                                <h4><?php echo htmlspecialchars($seller['name']); ?></h4>
                                
                                <?php if (!empty($seller['job']) || !empty($seller['company'])): ?>
                                <div class="seller-title">
                                    <?php 
                                    $title = [];
                                    if (!empty($seller['job'])) $title[] = $seller['job'];
                                    if (!empty($seller['company'])) $title[] = 'at ' . $seller['company'];
                                    echo implode(' ', $title);
                                    ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="seller-meta">
                                    <span><i class="fas fa-calendar"></i> Since <?php echo date('Y', strtotime($seller['created_at'])); ?></span>
                                    <span><i class="fas fa-box"></i> <?php echo $seller_products_count; ?> Products</span>
                                    <?php if ($seller_rating > 0): ?>
                                    <span><i class="fas fa-star" style="color: #fbbf24;"></i> <?php echo $seller_rating; ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($seller['location'])): ?>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo $seller['location']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($seller['bio'])): ?>
                                <p class="seller-bio"><?php echo htmlspecialchars($seller['bio']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="price-section">
                            <span class="current-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                            <span class="price-label">IDR</span>
                        </div>

                        <!-- Actions -->
                        <div class="action-buttons">
                            <?php if(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'buyer'): ?>
                                <form method="POST" action="add-to-cart.php" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn btn-primary btn-large">
                                        <i class="fas fa-shopping-bag"></i>
                                        Add to Cart
                                    </button>
                                </form>
                                <a href="checkout.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-large">
                                    <i class="fas fa-bolt"></i>
                                    Buy Now
                                </a>
                            <?php elseif(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'seller'): ?>
                                <?php if ($_SESSION['user']['id'] == $product['seller_id']): ?>
                                    <button class="btn btn-primary btn-large" disabled>
                                        <i class="fas fa-store"></i>
                                        Your Product
                                    </button>
                                <?php else: ?>
                                    <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-large">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Login to Purchase
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-large">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Login to Purchase
                                </a>
                                <a href="register.php" class="btn btn-outline btn-large">
                                    <i class="fas fa-user-plus"></i>
                                    Register
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Additional Info -->
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">File Type</span>
                                <span class="info-value"><?php echo $product['file_type'] ?? 'ZIP, Figma'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Compatibility</span>
                                <span class="info-value"><?php echo $product['compatibility'] ?? 'All Devices'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">License</span>
                                <span class="info-value"><?php echo $product['license'] ?? 'Commercial'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Review Form (Hanya untuk buyer yang sudah membeli) -->
            <?php if ($has_purchased): ?>
            <div class="review-form-section">
                <h3 class="review-form-title">
                    <?php echo $user_review ? 'Edit Your Review' : 'Write a Review'; ?>
                </h3>
                
                <form method="POST" id="reviewForm">
                    <input type="hidden" name="submit_review" value="1">
                    
                    <div class="rating-selector" id="ratingSelector">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star rating-star <?php echo ($user_review && $user_review['rating'] >= $i) ? 'selected' : ''; ?>" 
                           data-rating="<?php echo $i; ?>" onclick="setRating(<?php echo $i; ?>)"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="selectedRating" value="<?php echo $user_review['rating'] ?? 5; ?>" required>
                    
                    <textarea name="comment" class="review-textarea" placeholder="Share your experience with this product..." required minlength="10"><?php echo htmlspecialchars($user_review['comment'] ?? ''); ?></textarea>
                    <div class="char-count">
                        <span id="commentLength"><?php echo strlen($user_review['comment'] ?? ''); ?></span>/500 characters
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $user_review ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </form>
            </div>
            <?php elseif(isset($_SESSION['user']) && $_SESSION['user']['role'] === 'buyer'): ?>
            <div class="purchase-warning">
                <i class="fas fa-info-circle"></i>
                You can only review this product after purchasing it.
                <a href="checkout.php?product_id=<?php echo $product['id']; ?>" style="color: #0284c7; font-weight: 600; margin-left: 0.5rem;">Buy Now</a>
            </div>
            <?php endif; ?>

            <!-- Reviews -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <h2>Customer Reviews</h2>
                    <div class="reviews-summary">
                        <span class="average-rating"><?php echo $avg_rating; ?></span>
                        <div>
                            <div class="stars">
                                <?php 
                                for($i = 1; $i <= 5; $i++):
                                    if($i <= floor($avg_rating)):
                                ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif($i == ceil($avg_rating) && $avg_rating != floor($avg_rating)): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; 
                                endfor; ?>
                            </div>
                            <div style="color: #64748b; font-size: 0.85rem;">Based on <?php echo $total_reviews; ?> reviews</div>
                        </div>
                    </div>
                </div>

                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="reviewer">
                            <div class="reviewer-avatar">
                                <?php if (!empty($review['avatar']) && file_exists('assets/images/' . $review['avatar'])): ?>
                                    <img src="assets/images/<?php echo $review['avatar']; ?>" alt="<?php echo $review['name']; ?>">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="reviewer-info">
                                <h4>
                                    <?php echo htmlspecialchars($review['name']); ?>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $review['user_id']): ?>
                                    <span class="user-review-badge">Your Review</span>
                                    <?php endif; ?>
                                </h4>
                                <span class="date"><?php echo date('d M Y', strtotime($review['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#fbbf24' : '#e2e8f0'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <i class="fas fa-star" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No reviews yet. Be the first to review this product!</p>
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
        // Rating selector
        function setRating(rating) {
            document.getElementById('selectedRating').value = rating;
            
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }

        // Character counter
        const commentTextarea = document.querySelector('.review-textarea');
        const commentLength = document.getElementById('commentLength');
        
        if (commentTextarea && commentLength) {
            commentTextarea.addEventListener('input', function() {
                commentLength.textContent = this.value.length;
            });
        }

        // Initialize rating if editing
        <?php if ($user_review): ?>
        setRating(<?php echo $user_review['rating']; ?>);
        <?php endif; ?>
    </script>
</body>
</html>