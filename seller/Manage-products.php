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

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    
    try {
        // Cek apakah produk milik seller ini
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
        $stmt->execute([$product_id, $user_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Hapus gambar jika ada
            if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])) {
                unlink('../uploads/products/' . $product['image']);
            }
            
            // Hapus produk
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
            $stmt->execute([$product_id, $user_id]);
            
            $success = "Product deleted successfully!";
        } else {
            $error = "Product not found or you don't have permission to delete it.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Handle toggle featured
if (isset($_GET['toggle_featured'])) {
    $product_id = (int)$_GET['toggle_featured'];
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET featured = NOT featured WHERE id = ? AND seller_id = ?");
        $stmt->execute([$product_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Product featured status updated!";
        } else {
            $error = "Product not found or you don't have permission.";
        }
    } catch (PDOException $e) {
        $error = "Error updating product: " . $e->getMessage();
    }
}

// Get all products for this seller
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

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
    <title>Manage Products - Kreava Seller</title>
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #0c4a6e, #0284c7, #38bdf8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
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

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .search-input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.9rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #0284c7;
        }

        .filter-select {
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.9rem;
            background: white;
        }

        /* Products Table */
        .products-table-container {
            background: white;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 20px 40px -20px rgba(2, 132, 199, 0.2);
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .products-table td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.9rem;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .products-table tr:hover td {
            background: #f8fafc;
        }

        /* Product Info */
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0284c7;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .product-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }

        .product-details h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .product-details p {
            color: #64748b;
            font-size: 0.8rem;
        }

        /* Category Badge */
        .category-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Featured Badge */
        .featured-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: white;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .featured-badge i {
            font-size: 0.7rem;
        }

        /* Price */
        .product-price {
            font-weight: 700;
            color: #0284c7;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .action-btn.edit:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .action-btn.featured:hover {
            background: #f97316;
            color: white;
            border-color: #f97316;
        }

        .action-btn.delete:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 2rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            color: #475569;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .page-link.active {
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white;
            border-color: transparent;
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
        @media (max-width: 1024px) {
            .products-table {
                display: block;
                overflow-x: auto;
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-section {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
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
                <a href="manage-products.php" class="active">Manage Products</a>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">Manage Products</h1>
                <a href="add-product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </a>
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

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" class="search-input" id="searchInput" placeholder="Search products..." onkeyup="searchProducts()">
                    <button class="btn btn-primary" style="padding: 0.6rem 1.2rem;" onclick="searchProducts()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <select class="filter-select" id="categoryFilter" onchange="filterByCategory()">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Products Table -->
            <?php if (count($products) > 0): ?>
                <div class="products-table-container">
                    <table class="products-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="product-row" data-category="<?php echo $product['category'] ?? 'graphic'; ?>" data-name="<?php echo strtolower($product['name']); ?>">
                                <td>
                                    <div class="product-info">
                                        <div class="product-icon">
                                            <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                                                <img src="../uploads/products/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                                            <?php else: ?>
                                                <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <p><?php echo substr(htmlspecialchars($product['description']), 0, 50); ?>...</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge">
                                        <?php echo $categories[$product['category']] ?? ucfirst($product['category'] ?? 'Graphic'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                </td>
                                <td>
                                    <?php if ($product['featured']): ?>
                                    <span class="featured-badge">
                                        <i class="fas fa-star"></i>
                                        Featured
                                    </span>
                                    <?php else: ?>
                                    <span style="color: #94a3b8;">Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn edit" title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle_featured=<?php echo $product['id']; ?>" class="action-btn featured" title="<?php echo $product['featured'] ? 'Remove from Featured' : 'Mark as Featured'; ?>">
                                            <i class="fas fa-star"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="action-btn delete" title="Delete Product" onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link">4</a>
                    <a href="#" class="page-link">5</a>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <i class="fas fa-cube"></i>
                    <h3>No Products Yet</h3>
                    <p>Start your selling journey by adding your first product.</p>
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Your First Product
                    </a>
                </div>
            <?php endif; ?>
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
        // Search products
        function searchProducts() {
            let searchTerm = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('.product-row');
            
            rows.forEach(row => {
                let productName = row.getAttribute('data-name');
                if (productName.includes(searchTerm) || searchTerm === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter by category
        function filterByCategory() {
            let category = document.getElementById('categoryFilter').value;
            let rows = document.querySelectorAll('.product-row');
            
            rows.forEach(row => {
                let rowCategory = row.getAttribute('data-category');
                if (category === 'all' || rowCategory === category) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>