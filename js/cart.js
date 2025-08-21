class AutobubaCart {
    constructor() {
        this.cartKey = 'autobuba_cart';
        this.cartSidebar = document.getElementById('cartCars');
        this.init();
    }

    init() {
        // Initialize cart if it doesn't exist
        if (!this.getCart()) {
            this.saveCart([]);
        }
        this.updateCartCounter();
        
        // Set up event listeners
        this.setupEventListeners();

        window.AutobubaCart = cart;
    }

    

    setupEventListeners() {
        // Cart toggle
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cart')) {
                e.preventDefault();
                this.toggleCart();
            }
            
            if (e.target.closest('.selected-car-remove')) {
                const id = e.target.closest('.selected-car-remove').dataset.id;
                this.removeItem(id);
            }
            
            if (e.target.closest('#closeCartdisplay')) {
                this.closeCart();
            }
            
            // Add to cart buttons
            if (e.target.closest('.add-to-cart-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.add-to-cart-btn');
                const carData = {
                    id: btn.dataset.id,
                    name: btn.dataset.name,
                    price: btn.dataset.price,
                    img: btn.dataset.img,
                    type: btn.dataset.type
                };
                this.addItem(carData);
            }
        });
    }

    getCart() {
        return JSON.parse(localStorage.getItem(this.cartKey)) || [];
    }

    saveCart(cart) {
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
        this.updateCartCounter();
        this.updateCartDisplay();
        return cart;
    }

    addItem(item) {
        const cart = this.getCart();
        const exists = cart.some(cartItem => cartItem.id === item.id);
        
        if (!exists) {
            cart.push(item);
            this.saveCart(cart);
            this.showNotification(`${item.name} added to cart!`);
            return true;
        } else {
            this.showNotification("Item already in cart!");
            return false;
        }
    }

    removeItem(id) {
        let cart = this.getCart();
        cart = cart.filter(item => item.id !== id);
        this.saveCart(cart);
        this.showNotification("Item removed from cart");
        return cart;
    }

    updateCartCounter() {
        const cart = this.getCart();
        const counters = document.querySelectorAll('#cart-count');
        
        counters.forEach(counter => {
            counter.textContent = cart.length;
            counter.style.display = cart.length > 0 ? 'block' : 'none';
        });
    }

    updateCartDisplay() {
        if (!this.cartSidebar) return;
        
        const container = document.getElementById('selectedCars');
        if (!container) return;
        
        const cart = this.getCart();
        
        container.innerHTML = cart.length === 0 ? `
            <div class="empty-cart">
                <i class="fa fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some cars to get started!</p>
            </div>
        ` : cart.map(item => `
            <div class="selected-car-item">
                <div class="selected-car-img">
                    <img src="${item.img}" alt="${item.name}">
                </div>
                <div class="selected-car-info">
                    <div class="selected-car-name">${item.name}</div>
                    <div class="selected-car-price">
                        ${item.type === 'rent' ? `$${item.price}/mo` : `$${Number(item.price).toLocaleString()}`}
                    </div>
                </div>
                <button class="selected-car-remove" data-id="${item.id}">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    toggleCart() {
        this.cartSidebar.classList.toggle('show');
        this.updateCartDisplay();
    }

    closeCart() {
        this.cartSidebar.classList.remove('show');
    }

    showNotification(message) {
        const notification = document.createElement("div");
        notification.className = "notification alert alert-success";
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            padding: 10px 20px;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        `;

        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }

    
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.AutobubaCart = new AutobubaCart();
});