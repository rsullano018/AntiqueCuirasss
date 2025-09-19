/**
 * Product Catalog Page JavaScript
 * Handles product catalog functionality, filtering, and pagination
 */

class ProductCatalog {
    constructor() {
        this.api = window.apiClient;
        this.cartManager = window.cartManager;
        this.products = [];
        this.filteredProducts = [];
        this.currentPage = 1;
        this.productsPerPage = 12;
        this.filters = {
            category: '',
            condition: '',
            price: '',
            era: '',
            search: ''
        };
        this.sortBy = 'name';
        this.viewMode = 'grid';
        
        this.init();
    }
    
    /**
     * Initialize catalog
     */
    async init() {
        await this.loadCategories();
        this.loadProducts();
        this.bindEvents();
        this.parseURLParams();
        this.updateCartCount(); // Initialize cart count
    }
    
    /**
     * Load categories for filter dropdown
     */
    async loadCategories() {
        try {
            const response = await this.api.getCategories();
            if (response.success && response.data) {
                this.populateCategoryFilter(response.data);
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }
    
    /**
     * Populate category filter dropdown
     */
    populateCategoryFilter(categories) {
        const select = document.getElementById('category-filter');
        if (!select) return;
        
        select.innerHTML = '<option value="">All Categories</option>';
        
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.CategoryID;
            option.textContent = category.CategoryName;
            select.appendChild(option);
        });
    }
    
    /**
     * Load products from API
     */
    async loadProducts() {
        const container = document.getElementById('product-grid');
        if (!container) return;
        
        this.showLoading(container);
        
        try {
            const params = {
                limit: 100, // Load more products for client-side filtering
                ...this.filters
            };
            
            const response = await this.api.getProducts(params);
            
            if (response.success && response.data) {
                this.products = response.data;
                this.applyFilters();
                this.displayProducts();
                this.updateResultsCount();
            } else {
                this.showError(container, 'No products found');
            }
        } catch (error) {
            console.error('Failed to load products:', error);
            this.showError(container, 'Failed to load products');
        }
    }
    
    /**
     * Apply filters to products
     */
    applyFilters() {
        this.filteredProducts = this.products.filter(product => {
            // Category filter
            if (this.filters.category && product.CategoryID !== this.filters.category) {
                return false;
            }
            
            // Condition filter
            if (this.filters.condition && product.ConditionRating !== this.filters.condition) {
                return false;
            }
            
            // Price filter
            if (this.filters.price && parseFloat(product.Price) > parseFloat(this.filters.price)) {
                return false;
            }
            
            // Era filter
            if (this.filters.era && product.EraPeriod !== this.filters.era) {
                return false;
            }
            
            // Search filter
            if (this.filters.search) {
                const searchTerm = this.filters.search.toLowerCase();
                const searchableText = [
                    product.ProductName,
                    product.ShortDescription,
                    product.Material,
                    product.EraPeriod,
                    product.Manufacturer
                ].join(' ').toLowerCase();
                
                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }
            
            return true;
        });
        
        this.sortProducts();
        this.currentPage = 1;
    }
    
    /**
     * Sort products based on current sort option
     */
    sortProducts() {
        this.filteredProducts.sort((a, b) => {
            switch (this.sortBy) {
                case 'name':
                    return a.ProductName.localeCompare(b.ProductName);
                case 'name-desc':
                    return b.ProductName.localeCompare(a.ProductName);
                case 'price':
                    return parseFloat(a.Price) - parseFloat(b.Price);
                case 'price-desc':
                    return parseFloat(b.Price) - parseFloat(a.Price);
                case 'newest':
                    return new Date(b.DateCreated || 0) - new Date(a.DateCreated || 0);
                case 'oldest':
                    return new Date(a.DateCreated || 0) - new Date(b.DateCreated || 0);
                default:
                    return 0;
            }
        });
    }
    
    /**
     * Display products in the grid
     */
    displayProducts() {
        const container = document.getElementById('product-grid');
        if (!container) return;
        
        const startIndex = (this.currentPage - 1) * this.productsPerPage;
        const endIndex = startIndex + this.productsPerPage;
        const pageProducts = this.filteredProducts.slice(startIndex, endIndex);
        
        if (pageProducts.length === 0) {
            container.innerHTML = `
                <div class="no-products">
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search terms</p>
                    <button class="btn btn-primary" onclick="window.location.reload()">Reset Filters</button>
                </div>
            `;
            return;
        }
        
        container.innerHTML = pageProducts.map(product => this.createProductCard(product)).join('');
        this.updatePagination();
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
        
        const cardClass = this.viewMode === 'list' ? 'product-card list-view' : 'product-card';
        
        return `
            <div class="${cardClass}" data-product-id="${product.ProductID}">
                <div class="product-image">
                    <img src="${imageUrl}" alt="${product.ProductName}" loading="lazy">
                    <div class="product-badge">${product.ConditionRating || 'Authentic'}</div>
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.ProductName}</h3>
                    <p class="product-description">${product.ShortDescription || ''}</p>
                    <div class="product-details">
                        <div class="product-sku">SKU: ${product.SKU}</div>
                        <div class="product-era">${product.EraPeriod || 'Unknown Era'}</div>
                        <div class="product-material">${product.Material || 'Unknown Material'}</div>
                    </div>
                    <div class="product-price">
                        <span class="current-price">$${parseFloat(product.Price).toFixed(2)}</span>
                        ${originalPrice}
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary add-to-cart" data-product-id="${product.ProductID}">
                            Add to Cart
                        </button>
                        <button class="btn btn-secondary wishlist" data-product-id="${product.ProductID}">♡</button>
                        <a href="product-detail.html?id=${product.ProductID}" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Update pagination controls
     */
    updatePagination() {
        const container = document.getElementById('pagination');
        if (!container) return;
        
        const totalPages = Math.ceil(this.filteredProducts.length / this.productsPerPage);
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let paginationHTML = '<div class="pagination-controls">';
        
        // Previous button
        if (this.currentPage > 1) {
            paginationHTML += `<button class="pagination-btn" data-page="${this.currentPage - 1}">Previous</button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);
        
        if (startPage > 1) {
            paginationHTML += `<button class="pagination-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === this.currentPage ? 'active' : '';
            paginationHTML += `<button class="pagination-btn ${activeClass}" data-page="${i}">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="pagination-ellipsis">...</span>`;
            }
            paginationHTML += `<button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>`;
        }
        
        // Next button
        if (this.currentPage < totalPages) {
            paginationHTML += `<button class="pagination-btn" data-page="${this.currentPage + 1}">Next</button>`;
        }
        
        paginationHTML += '</div>';
        container.innerHTML = paginationHTML;
    }
    
    /**
     * Update results count display
     */
    updateResultsCount() {
        const element = document.getElementById('results-count');
        if (!element) return;
        
        const total = this.filteredProducts.length;
        const start = (this.currentPage - 1) * this.productsPerPage + 1;
        const end = Math.min(start + this.productsPerPage - 1, total);
        
        if (total === 0) {
            element.textContent = 'No products found';
        } else if (total <= this.productsPerPage) {
            element.textContent = `Showing ${total} product${total !== 1 ? 's' : ''}`;
        } else {
            element.textContent = `Showing ${start}-${end} of ${total} products`;
        }
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Filter controls
        const filterSelects = ['category-filter', 'condition-filter', 'price-range', 'era-filter'];
        filterSelects.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', (e) => {
                    this.updateFilter(id.replace('-filter', ''), e.target.value);
                });
            }
        });
        
        // Sort control
        const sortSelect = document.getElementById('sort-by');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.sortBy = e.target.value;
                this.applyFilters();
                this.displayProducts();
            });
        }
        
        // Clear filters button
        const clearFiltersBtn = document.getElementById('clear-filters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
        
        // View mode buttons
        const viewButtons = document.querySelectorAll('.view-btn');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setViewMode(e.target.dataset.view);
            });
        });
        
        // Pagination buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('pagination-btn')) {
                const page = parseInt(e.target.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.displayProducts();
                    this.scrollToTop();
                }
            }
        });
        
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                const productId = e.target.dataset.productId;
                if (productId) {
                    this.addToCart(productId);
                }
            }
        });
        
        // Search functionality
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.updateFilter('search', e.target.value);
            }, 300));
        }
    }
    
    /**
     * Update a specific filter
     */
    updateFilter(filterName, value) {
        this.filters[filterName] = value;
        this.applyFilters();
        this.displayProducts();
        this.updateURL();
    }
    
    /**
     * Clear all filters
     */
    clearFilters() {
        this.filters = {
            category: '',
            condition: '',
            price: '',
            era: '',
            search: ''
        };
        
        // Reset form elements
        document.getElementById('category-filter').value = '';
        document.getElementById('condition-filter').value = '';
        document.getElementById('price-range').value = '';
        document.getElementById('era-filter').value = '';
        document.getElementById('search-input').value = '';
        
        this.applyFilters();
        this.displayProducts();
        this.updateURL();
    }
    
    /**
     * Set view mode (grid or list)
     */
    setViewMode(mode) {
        this.viewMode = mode;
        
        // Update button states
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${mode}"]`).classList.add('active');
        
        // Update grid class
        const grid = document.getElementById('product-grid');
        if (grid) {
            grid.className = mode === 'list' ? 'product-grid list-view' : 'product-grid';
        }
        
        this.displayProducts();
    }
    
    /**
     * Parse URL parameters
     */
    parseURLParams() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Set search term
        const search = urlParams.get('search');
        if (search) {
            this.filters.search = search;
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value = search;
            }
        }
        
        // Set category filter
        const category = urlParams.get('category');
        if (category) {
            this.filters.category = category;
            const categorySelect = document.getElementById('category-filter');
            if (categorySelect) {
                categorySelect.value = category;
            }
        }
        
        // Apply filters if any were set
        if (search || category) {
            this.applyFilters();
            this.displayProducts();
        }
    }
    
    /**
     * Update URL with current filters
     */
    updateURL() {
        const url = new URL(window.location);
        url.search = '';
        
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            }
        });
        
        if (this.sortBy !== 'name') {
            url.searchParams.set('sort', this.sortBy);
        }
        
        if (this.currentPage > 1) {
            url.searchParams.set('page', this.currentPage);
        }
        
        window.history.replaceState({}, '', url);
    }
    
    /**
     * Scroll to top of products
     */
    scrollToTop() {
        const container = document.querySelector('.catalog-main');
        if (container) {
            container.scrollIntoView({ behavior: 'smooth' });
        }
    }
    
    /**
     * Show loading state
     */
    showLoading(element) {
        if (element) {
            element.innerHTML = '<div class="loading">Loading products...</div>';
        }
    }
    
    /**
     * Show error state
     */
    showError(element, message) {
        if (element) {
            element.innerHTML = `<div class="error">${message}</div>`;
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
    
    /**
     * Add product to cart
     */
    async addToCart(productId) {
        try {
            // Find the product in our loaded products
            const product = this.products.find(p => p.ProductID == productId);
            if (!product) {
                console.error('Product not found:', productId);
                this.showNotification('Product not found', 'error');
                return;
            }
            
            // Create cart item
            const cartItem = {
                id: Date.now().toString(),
                productId: productId,
                productName: product.ProductName,
                price: parseFloat(product.Price),
                quantity: 1,
                image: product.Images && product.Images.length > 0 ? product.Images[0].dataUrl : '/assets/images/placeholder.jpg'
            };
            
            // Add to localStorage cart
            const savedCart = localStorage.getItem('antique_cuirass_cart');
            const cart = savedCart ? JSON.parse(savedCart) : [];
            
            // Check if item already exists in cart
            const existingItem = cart.find(item => item.productId === productId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push(cartItem);
            }
            
            // Save to localStorage
            localStorage.setItem('antique_cuirass_cart', JSON.stringify(cart));
            
            // Update cart count in header
            this.updateCartCount();
            
            // Show success message
            this.showNotification(`${product.ProductName} added to cart!`, 'success');
            
            // Try to use cart manager if available
            if (this.cartManager) {
                await this.cartManager.addToCart(productId, 1);
            }
            
        } catch (error) {
            console.error('Failed to add item to cart:', error);
            this.showNotification('Failed to add item to cart', 'error');
        }
    }
    
    /**
     * Update cart count in header
     */
    updateCartCount() {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            const savedCart = localStorage.getItem('antique_cuirass_cart');
            if (savedCart) {
                const cart = JSON.parse(savedCart);
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                cartCountElement.textContent = totalItems;
            } else {
                cartCountElement.textContent = '0';
            }
        }
    }
    
    /**
     * Show notification message
     */
    showNotification(message, type = 'info') {
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `message message-${type}`;
        messageDiv.textContent = message;
        
        // Add styles
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 300px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        
        // Set background color based on type
        switch (type) {
            case 'success':
                messageDiv.style.backgroundColor = '#27ae60';
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#e74c3c';
                break;
            case 'warning':
                messageDiv.style.backgroundColor = '#f39c12';
                break;
            default:
                messageDiv.style.backgroundColor = '#3498db';
        }
        
        // Add to page
        document.body.appendChild(messageDiv);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }
}

// Initialize catalog when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.catalog = new ProductCatalog();
});

// Add CSS for catalog-specific styles
const style = document.createElement('style');
style.textContent = `
    .catalog-main {
        padding: 2rem 0;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .page-header h1 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }
    
    .catalog-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .filters-section {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-group label {
        font-weight: 600;
        color: var(--text-color);
        font-size: 0.9rem;
    }
    
    .filter-group select {
        padding: 0.5rem;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        background: var(--white);
        min-width: 120px;
    }
    
    .sort-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .sort-section label {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .sort-section select {
        padding: 0.5rem;
        border: 1px solid var(--light-gray);
        border-radius: var(--border-radius);
        background: var(--white);
    }
    
    .results-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding: 1rem 0;
        border-bottom: 1px solid var(--light-gray);
    }
    
    .results-count {
        font-weight: 600;
        color: var(--text-color);
    }
    
    .view-options {
        display: flex;
        gap: 0.5rem;
    }
    
    .view-btn {
        padding: 0.5rem;
        border: 1px solid var(--light-gray);
        background: var(--white);
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .view-btn.active {
        background: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }
    
    .view-btn:hover {
        background: var(--light-gray);
    }
    
    .view-btn.active:hover {
        background: var(--secondary-color);
    }
    
    .product-grid.list-view {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .product-grid.list-view .product-card {
        display: flex;
        flex-direction: row;
        align-items: center;
    }
    
    .product-grid.list-view .product-image {
        width: 200px;
        height: 150px;
        flex-shrink: 0;
    }
    
    .product-grid.list-view .product-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .product-grid.list-view .product-actions {
        margin-top: auto;
    }
    
    .product-details {
        display: flex;
        gap: 1rem;
        margin: 0.5rem 0;
        font-size: 0.9rem;
        color: var(--text-light);
    }
    
    .product-sku,
    .product-era,
    .product-material {
        background: var(--light-gray);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 3rem;
    }
    
    .pagination-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .pagination-btn {
        padding: 0.5rem 1rem;
        border: 1px solid var(--light-gray);
        background: var(--white);
        border-radius: var(--border-radius);
        cursor: pointer;
        transition: var(--transition);
    }
    
    .pagination-btn:hover {
        background: var(--light-gray);
    }
    
    .pagination-btn.active {
        background: var(--primary-color);
        color: var(--white);
        border-color: var(--primary-color);
    }
    
    .pagination-ellipsis {
        padding: 0.5rem;
        color: var(--text-light);
    }
    
    .no-products {
        text-align: center;
        padding: 3rem;
        color: var(--text-light);
    }
    
    .no-products h3 {
        margin-bottom: 1rem;
        color: var(--text-color);
    }
    
    @media (max-width: 768px) {
        .catalog-controls {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filters-section {
            justify-content: center;
        }
        
        .filter-group {
            min-width: 120px;
        }
        
        .results-info {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .product-grid.list-view .product-card {
            flex-direction: column;
        }
        
        .product-grid.list-view .product-image {
            width: 100%;
            height: 200px;
        }
    }
`;
document.head.appendChild(style);
