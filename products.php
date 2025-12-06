<?php 
include 'includes/header.php';

// Get category from URL
$current_category = $_GET['category'] ?? 'all';
$category_name = ucfirst(str_replace('-', ' ', $current_category));

// Check if category column exists
include 'includes/config.php';
$table_columns = $pdo->query("SHOW COLUMNS FROM products LIKE 'category'")->fetch();

?>
<div class="container">
    <section class="products-section">
        <div class="section-header">
            <h2 class="section-title"><?php echo $category_name === 'All' ? 'All Digital Products' : $category_name . ' Products'; ?></h2>
            <p class="section-subtitle">Explore our collection of premium creative assets</p>
        </div>

        <!-- Filter Options dengan Kategori -->
        <div class="products-filter">
            <div class="filter-header">
                <h3 class="filter-title">Categories</h3>
            </div>
            <div class="filter-options">
                <button class="filter-btn <?php echo $current_category === 'all' ? 'active' : ''; ?>" data-filter="all">
                    <i class="fas fa-th"></i>
                    All Products
                </button>
                <button class="filter-btn <?php echo $current_category === 'ui-ux' ? 'active' : ''; ?>" data-filter="ui-ux">
                    <i class="fas fa-mobile-alt"></i>
                    UI/UX Design
                </button>
                <button class="filter-btn <?php echo $current_category === 'graphic' ? 'active' : ''; ?>" data-filter="graphic">
                    <i class="fas fa-paint-brush"></i>
                    Graphic Design
                </button>
                <button class="filter-btn <?php echo $current_category === 'web' ? 'active' : ''; ?>" data-filter="web">
                    <i class="fas fa-laptop-code"></i>
                    Web Templates
                </button>
                <button class="filter-btn <?php echo $current_category === 'mobile' ? 'active' : ''; ?>" data-filter="mobile">
                    <i class="fas fa-mobile"></i>
                    Mobile Assets
                </button>
                <button class="filter-btn <?php echo $current_category === 'icons' ? 'active' : ''; ?>" data-filter="icons">
                    <i class="fas fa-icons"></i>
                    Icons & Illustrations
                </button>
            </div>
        </div>

        <div class="products-grid" id="productsGrid">
            <?php
            // Build query based on category and check if category column exists
            if ($table_columns && $current_category !== 'all') {
                $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY created_at DESC");
                $stmt->execute([$current_category]);
            } else {
                $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
            }
            
            while ($product = $stmt->fetch()):
                // Default category jika kolom belum ada
                $product_category = $table_columns ? ($product['category'] ?? 'ui-ux') : 'ui-ux';
            ?>
            <div class="product-card" data-category="<?php echo $product_category; ?>">
                <div class="product-header">
                    <div class="product-icon">
                        <i class="fas fa-<?php echo $product['icon'] ?? 'palette'; ?>"></i>
                    </div>
                    <?php if($product['featured']): ?>
                    <div class="product-badge">Featured</div>
                    <?php endif; ?>
                </div>
                
                <!-- Category Badge -->
                <?php if($table_columns): ?>
                <div class="category-badge">
                    <?php 
                    $category_icons = [
                        'ui-ux' => 'mobile-alt',
                        'graphic' => 'paint-brush',
                        'web' => 'laptop-code',
                        'mobile' => 'mobile',
                        'icons' => 'icons'
                    ];
                    ?>
                    <i class="fas fa-<?php echo $category_icons[$product_category] ?? 'palette'; ?>"></i>
                    <?php echo ucfirst($product_category); ?>
                </div>
                <?php endif; ?>
                
                <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                
                <div class="product-features">
                    <span class="feature-tag">Digital Download</span>
                    <span class="feature-tag">Lifetime Updates</span>
                    <span class="feature-tag">Premium Support</span>
                </div>
                
                <div class="product-footer">
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="product-actions">
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-small btn-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                        
                        <?php if(isset($_SESSION['user']) && isset($_SESSION['user']['role'])): ?>
                            <?php if($_SESSION['user']['role'] === 'buyer'): ?>
                                <!-- Buyer bisa add to cart -->
                                <form method="POST" action="add-to-cart.php" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn-small">
                                        <i class="fas fa-shopping-bag"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Seller hanya bisa lihat detail -->
                                <button class="btn-small" disabled title="Sellers cannot purchase products">
                                    <i class="fas fa-store"></i>
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- User belum login - redirect ke login -->
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small">
                                <i class="fas fa-shopping-bag"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    const sectionTitle = document.querySelector('.section-title');
    const sectionSubtitle = document.querySelector('.section-subtitle');
    
    // Add CSS for smooth transitions
    const style = document.createElement('style');
    style.textContent = `
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card.hidden {
            opacity: 0;
            transform: scale(0.8);
            height: 0;
            margin: 0;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filterValue = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter products with smooth transition
            productCards.forEach(card => {
                setTimeout(() => {
                    if (filterValue === 'all' || card.getAttribute('data-category') === filterValue) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }, 50);
            });
            
            // Update URL without page reload
            const newUrl = new URL(window.location);
            if (filterValue === 'all') {
                newUrl.searchParams.delete('category');
            } else {
                newUrl.searchParams.set('category', filterValue);
            }
            window.history.pushState({}, '', newUrl);
            
            // Update page title based on filter
            updatePageTitle(filterValue, this.textContent.trim());
        });
    });
    
    function updatePageTitle(filterValue, buttonText) {
        if (filterValue === 'all') {
            sectionTitle.textContent = 'All Digital Products';
            sectionSubtitle.textContent = 'Explore our collection of premium creative assets';
        } else {
            sectionTitle.textContent = buttonText + ' Products';
            sectionSubtitle.textContent = 'Discover our premium ' + buttonText.toLowerCase() + ' assets';
        }
    }
    
    // Handle browser back/forward buttons
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category') || 'all';
        
        // Update active button
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-filter') === category) {
                btn.classList.add('active');
                updatePageTitle(category, btn.textContent.trim());
            }
        });
        
        // Filter products
        productCards.forEach(card => {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>