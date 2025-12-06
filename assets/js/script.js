// Theme Toggle
class ThemeManager {
    constructor() {
        this.themeToggle = document.querySelector('.theme-toggle');
        this.themeIcon = this.themeToggle?.querySelector('i');
        this.init();
    }

    init() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        this.setTheme(savedTheme);
        
        this.themeToggle?.addEventListener('click', () => {
            this.toggleTheme();
        });
    }

    setTheme(theme) {
        document.documentElement.className = theme + '-theme';
        this.updateIcon(theme);
        localStorage.setItem('theme', theme);
    }

    toggleTheme() {
        const currentTheme = document.documentElement.className.includes('light') ? 'light' : 'dark';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }

    updateIcon(theme) {
        if (this.themeIcon) {
            this.themeIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }
}

// Product Card Animations
class ProductAnimations {
    constructor() {
        this.cards = document.querySelectorAll('.product-card');
        this.init();
    }

    init() {
        this.cards.forEach(card => {
            card.addEventListener('mouseenter', this.handleMouseEnter.bind(this));
            card.addEventListener('mouseleave', this.handleMouseLeave.bind(this));
        });
    }

    handleMouseEnter(e) {
        const card = e.currentTarget;
        card.style.transform = 'translateY(-10px) scale(1.02)';
    }

    handleMouseLeave(e) {
        const card = e.currentTarget;
        card.style.transform = 'translateY(0) scale(1)';
    }
}
// Product Filtering
class ProductFilter {
    constructor() {
        this.filterBtns = document.querySelectorAll('.filter-btn');
        this.productCards = document.querySelectorAll('.product-card');
        this.init();
    }

    init() {
        this.filterBtns.forEach(btn => {
            btn.addEventListener('click', () => this.handleFilter(btn));
        });
    }

    handleFilter(clickedBtn) {
        // Update active state
        this.filterBtns.forEach(btn => btn.classList.remove('active'));
        clickedBtn.classList.add('active');

        const filter = clickedBtn.dataset.filter;
        
        this.productCards.forEach(card => {
            if (filter === 'all' || card.dataset.category === filter) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    }
}

// Update initialization
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
    new ProductAnimations();
    new ProductFilter();
    
    document.body.classList.add('loaded');
});
// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
    new ProductAnimations();
    
    // Add loading animation
    document.body.classList.add('loaded');
});

// Product Filtering dengan Kategori
class ProductFilter {
    constructor() {
        this.filterBtns = document.querySelectorAll('.filter-btn');
        this.productCards = document.querySelectorAll('.product-card');
        this.init();
    }

    init() {
        this.filterBtns.forEach(btn => {
            btn.addEventListener('click', () => this.handleFilter(btn));
        });
    }

    handleFilter(clickedBtn) {
        // Update active state
        this.filterBtns.forEach(btn => btn.classList.remove('active'));
        clickedBtn.classList.add('active');

        const filter = clickedBtn.dataset.filter;
        
        // Update URL tanpa reload page
        const url = new URL(window.location);
        if (filter === 'all') {
            url.searchParams.delete('category');
        } else {
            url.searchParams.set('category', filter);
        }
        window.history.pushState({}, '', url);
        
        this.filterProducts(filter);
    }

    filterProducts(filter) {
        this.productCards.forEach(card => {
            if (filter === 'all' || card.dataset.category === filter) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
    }
}

// Update initialization
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
    new ProductAnimations();
    new ProductFilter();
    
    document.body.classList.add('loaded');
});