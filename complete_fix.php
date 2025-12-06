<?php
// complete_fix.php
include 'includes/config.php';

try {
    echo "Fixing database...<br>";
    
    // Insert default users
    $users = [
        ['id' => 1, 'name' => 'Default Seller', 'email' => 'seller@kreava.com', 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'role' => 'seller'],
        ['name' => 'John Buyer', 'email' => 'john@example.com', 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'role' => 'buyer'],
        ['name' => 'Sarah Designer', 'email' => 'sarah@example.com', 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'role' => 'seller']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($users as $user) {
        $id = $user['id'] ?? null;
        $stmt->execute([$id, $user['name'], $user['email'], $user['password'], $user['role']]);
    }
    
    echo "Users created successfully!<br>";
    
    // Add foreign key constraint
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("ALTER TABLE products ADD FOREIGN KEY (seller_id) REFERENCES users(id)");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Foreign key constraint added successfully!<br>";
    echo "Database is now ready!<br>";
    echo "<br><strong>Login credentials:</strong><br>";
    echo "Seller: seller@kreava.com / password<br>";
    echo "Buyer: john@example.com / password<br>";
    echo "Seller: sarah@example.com / password<br>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>