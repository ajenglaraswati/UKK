<?php 
session_start();
include 'includes/header.php';
include 'includes/functions.php'; // Tambahkan ini

// Check authentication dan role buyer
checkAuth();
if (!isBuyer()) {
    $_SESSION['error_message'] = "Sellers cannot access shopping cart. Please switch to a buyer account.";
    header('Location: index.php');
    exit();
}

// Add to cart logic
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        include 'includes/config.php';
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
    header('Location: cart.php');
    exit();
}

// Remove from cart
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    $_SESSION['success_message'] = "Product removed from cart!";
    header('Location: cart.php');
    exit();
}

// Tampilkan success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container">
    <h1 style="text-align: center; color: white; margin-bottom: 2rem;">Shopping Cart</h1>
    
    <div class="cart-items">
        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <?php 
            $total = 0;
            foreach ($_SESSION['cart'] as $id => $item):
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
            ?>
            <div class="cart-item">
                <div class="item-info">
                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                    <p>$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                </div>
                <div class="item-actions">
                    <span class="subtotal">$<?php echo number_format($subtotal, 2); ?></span>
                    <a href="cart.php?remove=<?php echo $id; ?>" class="btn btn-secondary">Remove</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="cart-total">
                Total: $<?php echo number_format($total, 2); ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="checkout.php" class="btn">Proceed to Checkout</a>
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h4>Your cart is empty</h4>
                <p>Add some products to get started</p>
                <a href="products.php" class="btn">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>