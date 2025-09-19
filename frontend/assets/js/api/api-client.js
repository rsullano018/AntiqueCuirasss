/**
 * API Client for Antique Cuirass E-Commerce
 * Handles all API communication with the backend
 */

class APIClient {
    constructor(baseURL = '/AntiqueCuirass/backend/api') {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('authToken');
        this.defaultHeaders = {
            'Content-Type': 'application/json'
        };
    }
    
    /**
     * Set authentication token
     */
    setToken(token) {
        this.token = token;
        if (token) {
            localStorage.setItem('authToken', token);
        } else {
            localStorage.removeItem('authToken');
        }
    }
    
    /**
     * Get authentication token
     */
    getToken() {
        return this.token;
    }
    
    /**
     * Make HTTP request
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            method: 'GET',
            headers: {
                ...this.defaultHeaders,
                ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error?.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    /**
     * Make direct HTTP request (bypasses baseURL)
     */
    async directRequest(url, options = {}) {
        const config = {
            method: 'GET',
            headers: {
                ...this.defaultHeaders,
                ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error?.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            
            return data;
        } catch (error) {
            console.error('Direct API Error:', error);
            throw error;
        }
    }
    
    // Product API methods
    async getProducts(params = {}) {
        // Try multiple product endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/products-simple.php',
            '/products',
            '/api/products'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    const queryString = new URLSearchParams(params).toString();
                    response = await this.request(`${endpoint}?${queryString}`);
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Get products endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All get products endpoints failed');
    }
    
    async getProduct(id) {
        return this.request(`/products/${id}`);
    }
    
    async getFeaturedProducts(limit = 6) {
        // Use the working products endpoint and filter for featured products
        const response = await this.getProducts();
        if (response.success && response.data) {
            // Filter for featured products and limit the results
            const featuredProducts = response.data
                .filter(product => product.Featured === true)
                .slice(0, limit);
            
            return {
                success: true,
                data: featuredProducts
            };
        }
        return response;
    }
    
    async getProductsByCategory(categoryId, limit = 20, offset = 0) {
        return this.request(`/products/category/${categoryId}?limit=${limit}&offset=${offset}`);
    }
    
    async searchProducts(query, filters = {}) {
        const params = { search: query, ...filters };
        return this.getProducts(params);
    }
    
    // Product Management API methods (for content managers and admins)
    async createProduct(productData) {
        // Try multiple product creation endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/products-simple.php',
            '/products',
            '/api/products'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(productData)
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(productData)
                    });
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Create product endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All create product endpoints failed');
    }
    
    async updateProduct(productId, productData) {
        // Try multiple product update endpoints
        const endpoints = [
            `/AntiqueCuirass/backend/products-simple.php/${productId}`,
            `/products/${productId}`,
            `/api/products/${productId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'PUT',
                        body: JSON.stringify(productData)
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'PUT',
                        body: JSON.stringify(productData)
                    });
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Update product endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All update product endpoints failed');
    }
    
    async deleteProduct(productId) {
        // Try multiple product delete endpoints
        const endpoints = [
            `/AntiqueCuirass/backend/products-simple.php/${productId}`,
            `/products/${productId}`,
            `/api/products/${productId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'DELETE'
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'DELETE'
                    });
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Delete product endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All delete product endpoints failed');
    }
    
    // Category API methods
    async getCategories() {
        // Try multiple categories endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/categories-simple.php',
            '/categories',
            '/api/categories'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    response = await this.request(endpoint);
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Categories endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All categories endpoints failed');
    }
    
    async getCategory(id) {
        // Try multiple category endpoints
        const endpoints = [
            `/AntiqueCuirass/backend/categories-simple.php/${id}`,
            `/categories/${id}`,
            `/api/categories/${id}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    response = await this.request(endpoint);
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Category endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All category endpoints failed');
    }
    
    // Cart API methods
    async getCart() {
        // Try multiple cart endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/cart-simple.php',
            '/cart',
            '/api/cart'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    response = await this.request(endpoint);
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Cart endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All cart endpoints failed');
    }
    
    async addToCart(productId, quantity = 1) {
        // Try multiple add to cart endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/cart-simple.php/add',
            '/cart/add',
            '/api/cart/add'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST',
                        body: JSON.stringify({ productId, quantity })
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST',
                        body: JSON.stringify({ productId, quantity })
                    });
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Add to cart endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All add to cart endpoints failed');
    }
    
    async updateCartItem(itemId, quantity) {
        // Try multiple update cart item endpoints
        const endpoints = [
            `/AntiqueCuirass/backend/cart-simple.php/${itemId}`,
            `/cart/items/${itemId}`,
            `/api/cart/items/${itemId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'PUT',
                        body: JSON.stringify({ quantity })
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'PUT',
                        body: JSON.stringify({ quantity })
                    });
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Update cart item endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All update cart item endpoints failed');
    }
    
    async removeFromCart(itemId) {
        // Try multiple remove from cart endpoints
        const endpoints = [
            `/AntiqueCuirass/backend/cart-simple.php/${itemId}`,
            `/cart/items/${itemId}`,
            `/api/cart/items/${itemId}`
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'DELETE'
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'DELETE'
                    });
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Remove from cart endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All remove from cart endpoints failed');
    }
    
    async clearCart() {
        // Try multiple clear cart endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/cart-simple.php/clear',
            '/cart/clear',
            '/api/cart/clear'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST'
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST'
                    });
                }
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Clear cart endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All clear cart endpoints failed');
    }
    
    // Order API methods
    async createOrder(orderData) {
        return this.request('/orders', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }
    
    async getOrder(orderId) {
        return this.request(`/orders/${orderId}`);
    }
    
    async getOrders() {
        return this.request('/orders');
    }
    
    async updateOrderStatus(orderId, status) {
        return this.request(`/orders/${orderId}/status`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
    }
    
    // User Authentication API methods
    async register(userData) {
        // Try multiple registration endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/register-test.php',
            '/users/register',
            '/api/users/register'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(userData)
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(userData)
                    });
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Registration endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All registration endpoints failed');
    }
    
    async login(credentials) {
        // Try multiple login endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/login-simple.php',
            '/users/login',
            '/api/users/login'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(credentials)
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST',
                        body: JSON.stringify(credentials)
                    });
                }
                
                if (response.success && response.data.token) {
                    this.setToken(response.data.token);
                    return response;
                }
            } catch (error) {
                console.log(`Login endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All login endpoints failed');
    }
    
    async logout() {
        // Try multiple logout endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/logout-simple.php',
            '/users/logout',
            '/api/users/logout'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint, {
                        method: 'POST'
                    });
                } else {
                    response = await this.request(endpoint, {
                        method: 'POST'
                    });
                }
                if (response.success) {
                    // Clear token after successful logout
                    this.setToken(null);
                    return response;
                }
            } catch (error) {
                console.log(`Logout endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        // Even if API fails, clear the token locally
        this.setToken(null);
        throw new Error('All logout endpoints failed');
    }
    
    async getProfile() {
        // Try multiple profile endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/profile-simple.php',
            '/AntiqueCuirass/backend/profile-test.php',
            '/AntiqueCuirass/backend/api/users/profile',
            '/profile-simple.php',
            '/profile-test.php',
            '/users/profile',
            '/api/users/profile'
        ];
        
        for (const endpoint of endpoints) {
            try {
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    response = await this.request(endpoint);
                }
                
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Profile endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All profile endpoints failed');
    }
    
    async updateProfile(profileData) {
        return this.request('/users/profile', {
            method: 'PUT',
            body: JSON.stringify(profileData)
        });
    }
    
    // Admin user management methods
    async getUsers() {
        // Try multiple users endpoints
        const endpoints = [
            '/AntiqueCuirass/backend/users-test.php',
            '/AntiqueCuirass/backend/api/users',
            '/users',
            '/api/users'
        ];
        
        for (const endpoint of endpoints) {
            try {
                console.log(`Trying endpoint: ${endpoint}`);
                let response;
                
                // Use directRequest for full URLs, regular request for relative paths
                if (endpoint.startsWith('/AntiqueCuirass/')) {
                    response = await this.directRequest(endpoint);
                } else {
                    response = await this.request(endpoint);
                }
                
                console.log(`Response from ${endpoint}:`, response);
                if (response.success) {
                    return response;
                }
            } catch (error) {
                console.log(`Users endpoint ${endpoint} failed:`, error.message);
                continue;
            }
        }
        
        throw new Error('All users endpoints failed');
    }
    
    async getUser(userId) {
        return this.request(`/users/${userId}`);
    }
    
    async updateUser(userId, userData) {
        return this.request(`/users/${userId}`, {
            method: 'PUT',
            body: JSON.stringify(userData)
        });
    }
    
    async deleteUser(userId) {
        return this.request(`/users/${userId}`, {
            method: 'DELETE'
        });
    }
    
    // Legacy customer methods for backward compatibility
    async registerCustomer(customerData) {
        return this.register(customerData);
    }
    
    async getCustomerProfile() {
        return this.getProfile();
    }
    
    async updateCustomerProfile(profileData) {
        return this.updateProfile(profileData);
    }
    
    async changePassword(passwordData) {
        return this.request('/users/change-password', {
            method: 'PUT',
            body: JSON.stringify(passwordData)
        });
    }
    
    // Wishlist API methods
    async getWishlist() {
        return this.request('/wishlist');
    }
    
    async addToWishlist(productId) {
        return this.request('/wishlist/add', {
            method: 'POST',
            body: JSON.stringify({ productId })
        });
    }
    
    async removeFromWishlist(productId) {
        return this.request(`/wishlist/remove/${productId}`, {
            method: 'DELETE'
        });
    }
    
    // Image API methods
    async uploadImage(file, productId = null) {
        const formData = new FormData();
        formData.append('image', file);
        if (productId) {
            formData.append('productId', productId);
        }
        
        return this.request('/images/upload', {
            method: 'POST',
            headers: {
                // Remove Content-Type header to let browser set it with boundary
                'Authorization': this.token ? `Bearer ${this.token}` : undefined
            },
            body: formData
        });
    }
    
    async deleteImage(imageId) {
        return this.request(`/images/${imageId}`, {
            method: 'DELETE'
        });
    }
    
    // Utility methods
    async healthCheck() {
        return this.request('/health');
    }
    
    isAuthenticated() {
        return !!this.token;
    }
    
    // Error handling
    handleError(error) {
        console.error('API Error:', error);
        
        if (error.message.includes('401')) {
            this.setToken(null);
            window.location.href = '/login.html';
            return;
        }
        
        if (error.message.includes('403')) {
            this.showNotification('Access denied. Please check your permissions.', 'error');
            return;
        }
        
        if (error.message.includes('404')) {
            this.showNotification('Resource not found.', 'error');
            return;
        }
        
        if (error.message.includes('500')) {
            this.showNotification('Server error. Please try again later.', 'error');
            return;
        }
        
        this.showNotification(error.message || 'An error occurred. Please try again.', 'error');
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        // Set background color based on type
        const colors = {
            success: '#10B981',
            error: '#EF4444',
            warning: '#F59E0B',
            info: '#3B82F6'
        };
        notification.style.backgroundColor = colors[type] || colors.info;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after delay
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
}

// Create global API client instance
window.apiClient = new APIClient();
