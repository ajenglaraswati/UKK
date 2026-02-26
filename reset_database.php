<?php
include 'includes/config.php';

try {
    // Nonaktifkan foreign key checks sementara
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Hapus semua data yang ada
    $pdo->exec("TRUNCATE TABLE order_items");
    $pdo->exec("TRUNCATE TABLE orders");
    $pdo->exec("TRUNCATE TABLE products");
    
    // Aktifkan kembali foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Insert products baru
    $products = [
        [1, 'UI/UX Design System', 'Complete design system with components and templates', 250000, 'palette', 1],
        [2, 'Mobile App UI Kit', 'Premium UI kit for mobile applications', 200000, 'mobile-alt', 1],
        [3, 'Icon Set Professional', '500+ premium icons in multiple formats', 120000, 'icons', 1],
        [4, 'Font Family Modern', 'Complete font family with 18 weights', 80000, 'font', 0],
        [5, 'Mockup Branding', 'Professional branding mockups', 180000, 'image', 0],
        [6, 'Illustration Pack', '500+ vector illustrations', 60000, 'paint-brush', 0]
    ];
    
    foreach ($products as $p) {
        $stmt = $pdo->prepare("INSERT INTO products (id, name, description, price, icon, featured) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($p);
    }
    
    // Insert orders
    $orders = [
        [1, 'ORD-2025-0001', 450000, 'completed', 'bank_transfer', 'paid', date('Y-m-d H:i:s', strtotime('-5 days'))],
        [1, 'ORD-2025-0002', 320000, 'paid', 'credit_card', 'paid', date('Y-m-d H:i:s', strtotime('-3 days'))],
        [1, 'ORD-2025-0003', 180000, 'processing', 'bank_transfer', 'paid', date('Y-m-d H:i:s', strtotime('-1 day'))],
        [1, 'ORD-2025-0004', 550000, 'pending', 'bank_transfer', 'pending', date('Y-m-d H:i:s')]
    ];
    
    foreach ($orders as $o) {
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($o);
    }
    
    // Get order IDs
    $order_ids = $pdo->query("SELECT id FROM orders ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    
    // Insert order items
    $items = [
        [$order_ids[0], 1, 'UI/UX Design System', 250000, 1, 250000],
        [$order_ids[0], 2, 'Mobile App UI Kit', 200000, 1, 200000],
        [$order_ids[1], 3, 'Icon Set Professional', 120000, 2, 240000],
        [$order_ids[1], 4, 'Font Family Modern', 80000, 1, 80000],
        [$order_ids[2], 5, 'Mockup Branding', 180000, 1, 180000],
        [$order_ids[3], 1, 'UI/UX Design System', 250000, 1, 250000],
        [$order_ids[3], 3, 'Icon Set Professional', 120000, 2, 240000],
        [$order_ids[3], 6, 'Illustration Pack', 60000, 1, 60000]
    ];
    
    foreach ($items as $i) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($i);
    }
    
    echo "Database has been reset successfully!<br>";
    echo "Products inserted: 6<br>";
    echo "Orders inserted: " . count($orders) . "<br>";
    echo "Order items inserted: " . count($items) . "<br>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>