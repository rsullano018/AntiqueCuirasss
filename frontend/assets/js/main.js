/**
 * Main JavaScript file for Antique Cuirass E-Commerce
 * Handles general site functionality and initialization
 */

class AntiqueCuirassApp {
    constructor() {
        this.api = window.apiClient;
        this.cartManager = window.cartManager;
        this.init();
    }
    
    /**
     * Initialize the application
     */
    init() {
        this.bindEvents();
        this.loadFeaturedProducts();
        this.initializeSearch();
        this.initializeMobileMenu();
        this.initializeUserAuth();
    }
    
    /**
     * Bind global event listeners
     */
    bindEvents() {
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navigation = document.querySelector('.main-navigation');
        
        if (mobileMenuToggle && navigation) {
            mobileMenuToggle.addEventListener('click', () => {
                navigation.classList.toggle('active');
                mobileMenuToggle.classList.toggle('active');
            });
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (navigation && !navigation.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                navigation.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });
        
        // Search functionality
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        
        if (searchInput && searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.performSearch(searchInput.value);
            });
            
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch(searchInput.value);
                }
            });
        }
        
        // Smooth scrolling for anchor links
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href^="#"]')) {
                e.preventDefault();
                const targetId = e.target.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
        
        // Lazy loading for images
        this.initializeLazyLoading();
        
        // Add to cart animations
        this.initializeAddToCartAnimations();
        
        // User authentication events
        this.bindUserAuthEvents();
    }
    
    /**
     * Load featured products on homepage
     */
    async loadFeaturedProducts() {
        const container = document.getElementById('featured-products');
        if (!container) return;
        
        try {
            const response = await this.api.getFeaturedProducts(6);
            if (response.success && response.data) {
                this.displayProducts(response.data, container);
            } else {
                container.innerHTML = '<div class="loading">No featured products available</div>';
            }
        } catch (error) {
            console.error('Failed to load featured products:', error);
            container.innerHTML = '<div class="loading">Failed to load featured products</div>';
        }
    }
    
    /**
     * Display products in a container
     */
    displayProducts(products, container) {
        if (!products || products.length === 0) {
            container.innerHTML = '<div class="loading">No products found</div>';
            return;
        }
        
        container.innerHTML = products.map(product => this.createProductCard(product)).join('');
    }
    
    /**
     * Create product card HTML
     */
    createProductCard(product) {
        // Handle both old format (product.image) and new format (product.Images array)
        let imageUrl = '/assets/images/placeholder.jpg';
        if (product.Images && product.Images.length > 0) {
            // New format: use first image from Images array
            imageUrl = product.Images[0].dataUrl;
        } else if (product.image) {
            // Old format: use single image property
            imageUrl = product.image;
        }
        
        const originalPrice = product.OriginalPrice && parseFloat(product.OriginalPrice) > parseFloat(product.Price) 
            ? `<span class="original-price">$${parseFloat(product.OriginalPrice).toFixed(2)}</span>` 
            : '';
        
        return `
            <div class="product-card" data-product-id="${product.ProductID}">
                <div class="product-image">
                    <img src="${imageUrl}" alt="${product.ProductName}" loading="lazy">
                    <div class="product-badge">${product.ConditionRating || 'Authentic'}</div>
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.ProductName}</h3>
                    <p class="product-description">${product.ShortDescription || ''}</p>
                    <div class="product-price">
                        <span class="current-price">$${parseFloat(product.Price).toFixed(2)}</span>
                        ${originalPrice}
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary add-to-cart" data-product-id="${product.ProductID}">
                            Add to Cart
                        </button>
                        <button class="btn btn-secondary wishlist" data-product-id="${product.ProductID}">♡</button>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize search functionality
     */
    initializeSearch() {
        const searchInput = document.getElementById('search-input');
        if (!searchInput) return;
        
        // Debounced search
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length >= 2) {
                    this.showSearchSuggestions(e.target.value);
                } else {
                    this.hideSearchSuggestions();
                }
            }, 300);
        });
    }
    
    /**
     * Show search suggestions
     */
    async showSearchSuggestions(query) {
        try {
            const response = await this.api.searchProducts(query, { limit: 5 });
            if (response.success && response.data) {
                this.displaySearchSuggestions(response.data);
            }
        } catch (error) {
            console.error('Search suggestions failed:', error);
        }
    }
    
    /**
     * Display search suggestions
     */
    displaySearchSuggestions(products) {
        let suggestionsContainer = document.getElementById('search-suggestions');
        if (!suggestionsContainer) {
            suggestionsContainer = document.createElement('div');
            suggestionsContainer.id = 'search-suggestions';
            suggestionsContainer.className = 'search-suggestions';
            document.querySelector('.search-box').appendChild(suggestionsContainer);
        }
        
        if (products.length === 0) {
            suggestionsContainer.innerHTML = '<div class="no-suggestions">No products found</div>';
        } else {
            suggestionsContainer.innerHTML = products.map(product => `
                <div class="suggestion-item" data-product-id="${product.ProductID}">
                    <img src="${product.image || '/assets/images/placeholder.jpg'}" alt="${product.ProductName}">
                    <div class="suggestion-info">
                        <h4>${product.ProductName}</h4>
                        <p>$${parseFloat(product.Price).toFixed(2)}</p>
                    </div>
                </div>
            `).join('');
        }
        
        suggestionsContainer.style.display = 'block';
    }
    
    /**
     * Hide search suggestions
     */
    hideSearchSuggestions() {
        const suggestionsContainer = document.getElementById('search-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }
    
    /**
     * Perform search and redirect to results
     */
    performSearch(query) {
        if (!query.trim()) return;
        
        const searchUrl = `product-catalog.html?search=${encodeURIComponent(query)}`;
        window.location.href = searchUrl;
    }
    
    /**
     * Initialize mobile menu
     */
    initializeMobileMenu() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navigation = document.querySelector('.main-navigation');
        
        if (mobileMenuToggle && navigation) {
            // Close menu when clicking on a link
            navigation.addEventListener('click', (e) => {
                if (e.target.matches('a')) {
                    navigation.classList.remove('active');
                    mobileMenuToggle.classList.remove('active');
                }
            });
        }
    }
    
    /**
     * Initialize lazy loading for images
     */
    initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    /**
     * Initialize add to cart animations
     */
    initializeAddToCartAnimations() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                const button = e.target;
                const originalText = button.textContent;
                
                // Add loading state
                button.textContent = 'Adding...';
                button.disabled = true;
                
                // Reset after animation
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 1000);
            }
        });
    }
    
    /**
     * Show loading state
     */
    showLoading(element) {
        if (element) {
            element.innerHTML = '<div class="loading">Loading...</div>';
        }
    }
    
    /**
     * Show error state
     */
    showError(element, message = 'An error occurred') {
        if (element) {
            element.innerHTML = `<div class="error">${message}</div>`;
        }
    }
    
    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }
    
    /**
     * Format date
     */
    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    /**
     * Initialize user authentication
     */
    async initializeUserAuth() {
        // Always show login link initially
        this.showLoginInterface();
        
        // Check if user is already logged in (optional)
        try {
            const response = await this.api.getProfile();
            if (response.success) {
                // User is logged in, but we'll still show login link for simplicity
                // The login modal will handle redirection based on role
                console.log('User already logged in:', response.data);
            }
        } catch (error) {
            // User not logged in, which is fine
            console.log('User not logged in');
        }
    }
    
    /**
     * Update user interface based on authentication status
     */
    updateUserInterface(user) {
        const loginLink = document.getElementById('login-link');
        
        if (loginLink) {
            if (user) {
                // User is logged in - hide login link
                loginLink.style.display = 'none';
            } else {
                // User is not logged in - show login link
                loginLink.style.display = 'block';
                loginLink.textContent = 'Login';
            }
        }
    }
    
    /**
     * Show login interface
     */
    showLoginInterface() {
        const loginLink = document.getElementById('login-link');
        
        if (loginLink) {
            loginLink.style.display = 'block';
            loginLink.textContent = 'Login';
        }
    }
    
    /**
     * Bind user authentication events
     */
    bindUserAuthEvents() {
        // Login link - navigate to login page
        const loginLink = document.getElementById('login-link');
        if (loginLink) {
            // Remove any existing event listeners
            loginLink.removeEventListener('click', this.showLoginModal);
            // The link will naturally navigate to login.html
        }
    }
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new AntiqueCuirassApp();
});

// Add CSS for search suggestions
const style = document.createElement('style');
style.textContent = `
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        max-height: 300px;
        overflow-y: auto;
        display: none;
    }
    
    .suggestion-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .suggestion-item:hover {
        background-color: #f8f8f8;
    }
    
    .suggestion-item img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 0.75rem;
    }
    
    .suggestion-info h4 {
        margin: 0 0 0.25rem 0;
        font-size: 0.9rem;
        color: #333;
    }
    
    .suggestion-info p {
        margin: 0;
        font-size: 0.8rem;
        color: #666;
        font-weight: 600;
    }
    
    .no-suggestions {
        padding: 1rem;
        text-align: center;
        color: #666;
        font-style: italic;
    }
    
    .error {
        text-align: center;
        padding: 2rem;
        color: #e74c3c;
        font-weight: 500;
    }
    
    .user-menu {
        position: relative;
    }
`;
document.head.appendChild(style);
