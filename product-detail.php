<?php
include 'includes/header.php';
include 'includes/config.php';

$product_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}
?>

<div class="container">
    <div class="product-detail">
        <div class="product-detail-grid">
            <div class="product-image-large">
                <div class="image-placeholder">
                    <i class="fas fa-<?php echo $product['icon'] ?? 'cube'; ?>"></i>
                </div>
            </div>
            
            <div class="product-info">
                <div class="product-meta">
                    <span class="category-badge"><?php echo ucfirst($product['category'] ?? 'ui-ux'); ?></span>
                    <?php if($product['featured']): ?>
                    <span class="featured-badge">Featured</span>
                    <?php endif; ?>
                </div>
                
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                
                <div class="product-features">
                    <div class="feature">
                        <i class="fas fa-download"></i>
                        <span>Instant Digital Download</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-sync"></i>
                        <span>Lifetime Updates</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-headset"></i>
                        <span>Premium Support</span>
                    </div>
                </div>
                
                <div class="product-price-large">
                    $<?php echo number_format($product['price'], 2); ?>
                </div>
                
                <div class="product-actions-large">
                    <?php if(isset($_SESSION['user'])): ?>
                        <?php if($_SESSION['user']['role'] === 'buyer'): ?>
                            <!-- Buyer bisa add to cart -->
                            <form method="POST" action="add-to-cart.php" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="add_to_cart" class="btn btn-large">
                                    <i class="fas fa-shopping-bag"></i>
                                    Add to Cart
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Seller tidak bisa beli -->
                            <button class="btn btn-large" disabled>
                                <i class="fas fa-store"></i>
                                Sellers Cannot Purchase
                            </button>
                            <p class="info-text">As a seller, you cannot purchase products. Switch to buyer account to make purchases.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- User belum login -->
                        <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-large">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Purchase
                        </a>
                        <p class="info-text">Please login or register to purchase this product.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>