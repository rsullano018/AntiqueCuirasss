/**
 * Cart Manager Component
 * Handles shopping cart functionality
 */

class CartManager {
    constructor() {
        this.api = window.apiClient;
        this.cart = [];
        this.cartKey = 'antique_cuirass_cart';
        this.init();
    }
    
    /**
     * Initialize cart manager
     */
    async init() {
        await this.loadCart();
        this.updateCartUI();
        this.bindEvents();
    }
    
    /**
     * Load cart from API or localStorage
     */
    async loadCart() {
        try {
            if (this.api.isAuthenticated()) {
                const response = await this.api.getCart();
                this.cart = response.data || [];
            } else {
                // Load from localStorage for guest users
                const savedCart = localStorage.getItem(this.cartKey);
                this.cart = savedCart ? JSON.parse(savedCart) : [];
            }
        } catch (error) {
            console.error('Failed to load cart:', error);
            this.cart = [];
        }
    }
    
    /**
     * Save cart to localStorage
     */
    saveCart() {
        if (!this.api.isAuthenticated()) {
            localStorage.setItem(this.cartKey, JSON.stringify(this.cart));
        }
    }
    
    /**
     * Add item to cart
     */
    async addToCart(productId, quantity = 1) {
        try {
            // Check if item already exists in cart
            const existingItem = this.cart.find(item => item.productId === productId);
            
            if (existingItem) {
                // Update quantity
                await this.updateCartItem(existingItem.id, existingItem.quantity + quantity);
            } else {
                // Add new item
                if (this.api.isAuthenticated()) {
                    const response = await this.api.addToCart(productId, quantity);
                    if (response.success) {
                        await this.loadCart();
                    }
                } else {
                    // For guest users, add to local cart
                    const product = await this.getProductDetails(productId);
                    if (product) {
                        const cartItem = {
                            id: Date.now().toString(),
                            productId: productId,
                            productName: product.ProductName,
                            price: product.Price,
                            quantity: quantity,
                            image: product.image || '/assets/images/placeholder.jpg'
                        };
                        this.cart.push(cartItem);
                        this.saveCart();
                    }
                }
            }
            
            this.updateCartUI();
            this.showNotification('Item added to cart!', 'success');
            
        } catch (error) {
            console.error('Failed to add item to cart:', error);
            this.showNotification('Failed to add item to cart', 'error');
        }
    }
    
    /**
     * Update cart item quantity
     */
    async updateCartItem(itemId, quantity) {
        try {
            if (quantity <= 0) {
                await this.removeFromCart(itemId);
                return;
            }
            
            if (this.api.isAuthenticated()) {
                const response = await this.api.updateCartItem(itemId, quantity);
                if (response.success) {
                    await this.loadCart();
                }
            } else {
                // Update local cart
                const item = this.cart.find(item => item.id === itemId);
                if (item) {
                    item.quantity = quantity;
                    this.saveCart();
                }
            }
            
            this.updateCartUI();
            
        } catch (error) {
            console.error('Failed to update cart item:', error);
            this.showNotification('Failed to update cart item', 'error');
        }
    }
    
    /**
     * Remove item from cart
     */
    async removeFromCart(itemId) {
        try {
            if (this.api.isAuthenticated()) {
                const response = await this.api.removeFromCart(itemId);
                if (response.success) {
                    await this.loadCart();
                }
            } else {
                // Remove from local cart
                this.cart = this.cart.filter(item => item.id !== itemId);
                this.saveCart();
            }
            
            this.updateCartUI();
            this.showNotification('Item removed from cart', 'success');
            
        } catch (error) {
            console.error('Failed to remove item from cart:', error);
            this.showNotification('Failed to remove item from cart', 'error');
        }
    }
    
    /**
     * Clear entire cart
     */
    async clearCart() {
        try {
            if (this.api.isAuthenticated()) {
                const response = await this.api.clearCart();
                if (response.success) {
                    this.cart = [];
                }
            } else {
                this.cart = [];
                this.saveCart();
            }
            
            this.updateCartUI();
            this.showNotification('Cart cleared', 'success');
            
        } catch (error) {
            console.error('Failed to clear cart:', error);
            this.showNotification('Failed to clear cart', 'error');
        }
    }
    
    /**
     * Get cart total
     */
    getCartTotal() {
        return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
    
    /**
     * Get cart item count
     */
    getCartItemCount() {
        return this.cart.reduce((count, item) => count + item.quantity, 0);
    }
    
    /**
     * Update cart UI elements
     */
    updateCartUI() {
        // Update cart count
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(element => {
            element.textContent = this.getCartItemCount();
        });
        
        // Update cart total
        const cartTotalElements = document.querySelectorAll('.cart-total');
        cartTotalElements.forEach(element => {
            element.textContent = `$${this.getCartTotal().toFixed(2)}`;
        });
        
        // Update cart items if cart page is open
        this.updateCartItemsList();
    }
    
    /**
     * Update cart items list on cart page
     */
    updateCartItemsList() {
        const cartItemsContainer = document.getElementById('cart-items');
        if (!cartItemsContainer) return;
        
        if (this.cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <p>Add some authentic antiques to get started!</p>
                    <a href="product-catalog.html" class="btn btn-primary">Browse Products</a>
                </div>
            `;
            return;
        }
        
        cartItemsContainer.innerHTML = this.cart.map(item => `
            <div class="cart-item" data-item-id="${item.id}">
                <div class="item-image">
                    <img src="${item.image}" alt="${item.productName}">
                </div>
                <div class="item-details">
                    <h4 class="item-name">${item.productName}</h4>
                    <p class="item-price">$${item.price.toFixed(2)}</p>
                </div>
                <div class="item-quantity">
                    <button class="quantity-btn minus" data-item-id="${item.id}">-</button>
                    <input type="number" value="${item.quantity}" min="1" max="99" data-item-id="${item.id}">
                    <button class="quantity-btn plus" data-item-id="${item.id}">+</button>
                </div>
                <div class="item-total">$${(item.price * item.quantity).toFixed(2)}</div>
                <button class="remove-item" data-item-id="${item.id}">×</button>
            </div>
        `).join('');
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                const productId = e.target.dataset.productId;
                const quantity = parseInt(e.target.dataset.quantity) || 1;
                this.addToCart(productId, quantity);
            }
        });
        
        // Cart item controls
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                const itemId = e.target.dataset.itemId;
                const input = document.querySelector(`input[data-item-id="${itemId}"]`);
                let quantity = parseInt(input.value);
                
                if (e.target.classList.contains('plus')) {
                    quantity++;
                } else if (e.target.classList.contains('minus')) {
                    quantity--;
                }
                
                this.updateCartItem(itemId, quantity);
            }
            
            if (e.target.classList.contains('remove-item')) {
                const itemId = e.target.dataset.itemId;
                this.removeFromCart(itemId);
            }
        });
        
        // Quantity input changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('input[data-item-id]')) {
                const itemId = e.target.dataset.itemId;
                const quantity = parseInt(e.target.value);
                this.updateCartItem(itemId, quantity);
            }
        });
    }
    
    /**
     * Get product details for guest cart
     */
    async getProductDetails(productId) {
        try {
            const response = await this.api.getProduct(productId);
            return response.data;
        } catch (error) {
            console.error('Failed to get product details:', error);
            return null;
        }
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        if (window.apiClient && window.apiClient.showNotification) {
            window.apiClient.showNotification(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
    
    /**
     * Get cart data for checkout
     */
    getCartData() {
        return {
            items: this.cart,
            total: this.getCartTotal(),
            itemCount: this.getCartItemCount()
        };
    }
}

// Initialize cart manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.cartManager = new CartManager();
});
