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

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product data
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error_message'] = "Product not found or you don't have permission to edit it.";
    header('Location: manage-products.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = str_replace('.', '', $_POST['price']); // Hapus titik dari format Rupiah
    $price = floatval($price);
    $icon = $_POST['icon'];
    $file_link = trim($_POST['file_link']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validation
    if (empty($category) || empty($name) || empty($description) || empty($price) || empty($icon) || empty($file_link)) {
        $error = 'Please fill in all required fields.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } elseif (!filter_var($file_link, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid URL for the product file.';
    } else {
        try {
            // Handle file upload for new image
            $image_name = $product['image']; // Keep old image by default
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/products/';
                
                // Buat folder jika belum ada
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = time() . '_' . basename($_FILES['product_image']['name']);
                $target_file = $upload_dir . $file_name;
                $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                
                // Check if image file is actual image
                $check = getimagesize($_FILES['product_image']['tmp_name']);
                if ($check === false) {
                    $error = "File is not an image.";
                } elseif ($_FILES['product_image']['size'] > 5000000) { // 5MB max
                    $error = "File is too large. Max size 5MB.";
                } elseif (!in_array($image_file_type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $error = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
                } else {
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                        // Hapus gambar lama jika ada
                        if (!empty($product['image']) && file_exists($upload_dir . $product['image'])) {
                            unlink($upload_dir . $product['image']);
                        }
                        $image_name = $file_name;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }
            
            // Handle remove image
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])) {
                    unlink('../uploads/products/' . $product['image']);
                }
                $image_name = null;
            }
            
            if (empty($error)) {
                // Update product - HAPUS bagian updated_at
                $stmt = $pdo->prepare("
                    UPDATE products SET 
                        category = ?, 
                        name = ?, 
                        description = ?, 
                        price = ?, 
                        icon = ?, 
                        image = ?, 
                        file_link = ?, 
                        featured = ?
                    WHERE id = ? AND seller_id = ?
                ");
                $stmt->execute([$category, $name, $description, $price, $icon, $image_name, $file_link, $featured, $product_id, $user_id]);
                
                $success = 'Product updated successfully!';
                
                // Refresh product data
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
                $stmt->execute([$product_id, $user_id]);
                $product = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Error updating product: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = [
    'ui-ux' => 'UI/UX Design',
    'graphic' => 'Graphic Design',
    'web' => 'Web Templates',
    'mobile' => 'Mobile Assets',
    'illustration' => 'Illustrations',
    'font' => 'Fonts',
    '3d' => '3D Models',
    'video' => 'Video Assets',
    'audio' => 'Audio Assets',
    'code' => 'Code & Scripts'
];

// Get icons
$icons = [
    'palette' => 'Design',
    'mobile-alt' => 'Mobile',
    'laptop-code' => 'Web',
    'paint-brush' => 'Art',
    'image' => 'Image',
    'font' => 'Font',
    'cube' => '3D',
    'video' => 'Video',
    'music' => 'Audio',
    'code' => 'Code'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Kreava Seller</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #0284c7;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #475569;
            font-size: 1rem;
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 30px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
            border: 1px solid #e2e8f0;
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
            background: #f8fafc;
            border-radius: 20px;
            padding: 1.8rem;
            border: 1px solid #e2e8f0;
        }

        .form-section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-section-title i {
            color: #0284c7;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-label i {
            color: #0284c7;
            width: 18px;
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 4px;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.8rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
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
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 0.3rem;
        }

        /* Format Rupiah */
        .price-input-wrapper {
            position: relative;
        }

        .price-prefix {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-weight: 500;
        }

        .price-input {
            padding-left: 3rem !important;
        }

        /* File Upload */
        .current-image {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f0f9ff;
            border-radius: 12px;
            border: 1px solid #bae6fd;
            margin-bottom: 1rem;
        }

        .current-image img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .current-image-info {
            flex: 1;
        }

        .current-image-info h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .current-image-info p {
            color: #64748b;
            font-size: 0.85rem;
        }

        .remove-image {
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .file-upload:hover {
            border-color: #0284c7;
            background: #f0f9ff;
        }

        .file-upload i {
            font-size: 2.5rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .file-upload p {
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .file-upload small {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f0f9ff;
            border-radius: 8px;
            border: 1px solid #bae6fd;
        }

        .file-info.active {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-preview {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
        }

        .file-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .file-size {
            color: #64748b;
            font-size: 0.85rem;
        }

        .file-remove {
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* Icon Selection */
        .icon-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(50px, 1fr));
            gap: 0.5rem;
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
            width: 50px;
            height: 50px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1.3rem;
            transition: all 0.3s ease;
            margin: 0 auto 0.3rem;
        }

        .icon-option input:checked + .icon-preview {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: #0284c7;
            transform: scale(1.05);
        }

        .icon-name {
            font-size: 0.65rem;
            color: #64748b;
        }

        /* File Link */
        .file-link-hint {
            background: #f0f9ff;
            padding: 0.8rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #0284c7;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-link-hint i {
            font-size: 1rem;
        }

        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .checkbox input {
            width: 18px;
            height: 18px;
            accent-color: #0284c7;
        }

        .featured-badge {
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
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
        }

        .btn-danger {
            background: white;
            color: #ef4444;
            border: 1px solid #ef4444;
        }

        .btn-danger:hover {
            background: #ef4444;
            color: white;
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
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
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
                <a href="manage-products.php">Manage Products</a>
                <a href="add-product.php">Add Product</a>
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
            <!-- Back Link -->
            <div class="page-header">
                <a href="manage-products.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Manage Products
                </a>
                <h1 class="page-title">Edit Product</h1>
                <p class="page-subtitle">Update your product information</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <form method="POST" enctype="multipart/form-data" class="form-container">
                <div class="form-grid">
                    <!-- Left Column - Basic Info -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h3>

                        <!-- Category -->
                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-folder"></i>
                                Category
                            </label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($product['category'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-tag"></i>
                                Product Name
                            </label>
                            <input type="text" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>" 
                                   placeholder="e.g. Modern UI Kit" required maxlength="100">
                            <div class="char-count">Max 100 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea name="description" class="form-textarea" 
                                      placeholder="Describe your product in detail..." 
                                      required maxlength="1000"><?php echo htmlspecialchars($product['description']); ?></textarea>
                            <div class="char-count">Max 1000 characters</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-dollar-sign"></i>
                                Price (Rp)
                            </label>
                            <div class="price-input-wrapper">
                                <span class="price-prefix">Rp</span>
                                <input type="text" name="price" class="form-input price-input" id="price-input"
                                       value="<?php echo number_format($product['price'], 0, ',', '.'); ?>" 
                                       placeholder="150.000" required onkeyup="formatPrice(this)">
                            </div>
                        </div>

                        <!-- Featured Checkbox -->
                        <div class="checkbox-group">
                            <label class="checkbox">
                                <input type="checkbox" name="featured" value="1" 
                                       <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                <span>Feature this product</span>
                            </label>
                            <span class="featured-badge">Featured</span>
                        </div>
                    </div>

                    <!-- Right Column - Media & Details -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-image"></i>
                            Product Media
                        </h3>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-camera"></i>
                                Product Image
                            </label>
                            
                            <!-- Current Image -->
                            <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                            <div class="current-image">
                                <img src="../uploads/products/<?php echo $product['image']; ?>" alt="Current product image">
                                <div class="current-image-info">
                                    <h4>Current Image</h4>
                                    <p><?php echo $product['image']; ?></p>
                                </div>
                                <label class="remove-image">
                                    <input type="checkbox" name="remove_image" value="1" style="display: none;">
                                    <i class="fas fa-times-circle" onclick="toggleRemoveImage(this)"></i>
                                </label>
                            </div>
                            <?php endif; ?>

                            <!-- Upload New Image -->
                            <div class="file-upload" onclick="document.getElementById('product_image').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload new image</p>
                                <small>Supports: JPG, PNG, GIF, WEBP (Max 5MB)</small>
                            </div>
                            <input type="file" name="product_image" id="product_image" accept="image/*" style="display: none;" onchange="handleFileSelect(this)">
                            
                            <div class="file-info" id="file-info">
                                <div class="file-preview" id="file-preview">
                                    <img src="" alt="Preview">
                                </div>
                                <div class="file-details">
                                    <div class="file-name" id="file-name"></div>
                                    <div class="file-size" id="file-size"></div>
                                </div>
                                <div class="file-remove" onclick="removeFile()">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-icons"></i>
                                Icon
                            </label>
                            <div class="icon-selection">
                                <?php foreach ($icons as $icon => $name): ?>
                                <label class="icon-option">
                                    <input type="radio" name="icon" value="<?php echo $icon; ?>" 
                                           <?php echo ($product['icon'] ?? 'palette') === $icon ? 'checked' : ''; ?> required>
                                    <div class="icon-preview">
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="icon-name"><?php echo $name; ?></div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- File Link untuk produk digital -->
                        <div class="form-group">
                            <label class="form-label required">
                                <i class="fas fa-link"></i>
                                Product File URL
                            </label>
                            <input type="url" name="file_link" class="form-input" 
                                   value="<?php echo htmlspecialchars($product['file_link'] ?? ''); ?>" 
                                   placeholder="https://drive.google.com/your-file" required>
                            <div class="file-link-hint">
                                <i class="fas fa-info-circle"></i>
                                Link ke file produk (Google Drive, Dropbox, atau cloud storage lainnya)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="manage-products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Product
                    </button>
                </div>
            </form>
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
        // Format Rupiah saat mengetik
        function formatPrice(input) {
            let value = input.value.replace(/[^\d]/g, '');
            if (value) {
                let formatted = new Intl.NumberFormat('id-ID').format(value);
                input.value = formatted;
            }
        }

        // Toggle remove image checkbox
        function toggleRemoveImage(icon) {
            const checkbox = icon.parentElement.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                icon.style.color = '#ef4444';
                icon.style.opacity = '1';
            } else {
                icon.style.color = '#94a3b8';
                icon.style.opacity = '0.5';
            }
        }

        // File upload handling
        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                const fileInfo = document.getElementById('file-info');
                const filePreview = document.getElementById('file-preview').querySelector('img');
                const fileName = document.getElementById('file-name');
                const fileSize = document.getElementById('file-size');

                reader.onload = function(e) {
                    filePreview.src = e.target.result;
                    fileName.textContent = file.name;
                    fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                    fileInfo.classList.add('active');
                };

                reader.readAsDataURL(file);
            }
        }

        function removeFile() {
            const fileInput = document.getElementById('product_image');
            const fileInfo = document.getElementById('file-info');
            
            fileInput.value = '';
            fileInfo.classList.remove('active');
        }

        // Character counter for description
        const descriptionTextarea = document.querySelector('textarea[name="description"]');
        const charCount = document.querySelector('.char-count');
        
        if (descriptionTextarea && charCount) {
            descriptionTextarea.addEventListener('input', function() {
                const remaining = 1000 - this.value.length;
                charCount.textContent = `Max 1000 characters (${remaining} remaining)`;
            });
        }
    </script>
</body>
</html>