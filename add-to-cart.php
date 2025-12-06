<?php
session_start();
include 'includes/config.php';
include 'includes/functions.php'; // Tambahkan ini

// Check jika user belum login
if (!isset($_SESSION['user'])) {
    $_SESSION['error_message'] = "Please login to add products to cart";
    header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'products.php'));
    exit();
}

// Check jika user bukan buyer
if (!isBuyer()) {
    $_SESSION['error_message'] = "Sellers cannot purchase products. Please switch to a buyer account.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
    exit();
}

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add to cart logic
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1,
                'image' => $product['icon']
            ];
        }
    }
    
    $_SESSION['success_message'] = "Product added to cart successfully!";
}

// Redirect back to previous page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
exit();
?>