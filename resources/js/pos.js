// resources/js/pos.js
// POS front-end logic: product search via API, checkout via API, simple offline skeleton using localStorage.

export function erpPosTerminal(options) {
    const branchId = options.branchId;

    return {
        branchId,
        search: '',
        isSearching: false,
        products: [],
        cart: [],
        isCheckingOut: false,
        message: null,
        offline: !window.navigator.onLine,

        init() {
            this.loadCart();
            this.loadOfflineQueue();

            window.addEventListener('online', () => {
                this.offline = false;
            });

            window.addEventListener('offline', () => {
                this.offline = true;
            });
        },

        get storageKey() {
            return `erp_pos_cart_branch_${this.branchId}`;
        },

        get offlineQueueKey() {
            return `erp_pos_offline_sales_branch_${this.branchId}`;
        },

        loadCart() {
            try {
                const raw = window.localStorage.getItem(this.storageKey);
                this.cart = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('Failed to load POS cart from storage', e);
                this.cart = [];
            }
        },

        persistCart() {
            try {
                window.localStorage.setItem(this.storageKey, JSON.stringify(this.cart));
            } catch (e) {
                console.error('Failed to persist POS cart to storage', e);
            }
        },

        loadOfflineQueue() {
            try {
                const raw = window.localStorage.getItem(this.offlineQueueKey);
                this.offlineQueue = raw ? JSON.parse(raw) : [];
            } catch (e) {
                console.error('Failed to load POS offline queue', e);
                this.offlineQueue = [];
            }
        },

        persistOfflineQueue() {
            try {
                window.localStorage.setItem(this.offlineQueueKey, JSON.stringify(this.offlineQueue ?? []));
            } catch (e) {
                console.error('Failed to persist POS offline queue', e);
            }
        },

        
        async syncOfflineQueue() {
            if (!this.offlineQueue || !this.offlineQueue.length) {
                this.message = {
                    type: 'info',
                    text: 'لا توجد طلبات Offline للمزامنة.',
                };
                return;
            }

            const queue = [...this.offlineQueue];

            for (const item of queue) {
                try {
                    await window.axios.post(`/api/v1/branches/${this.branchId}/pos/checkout`, item.payload ?? {});
                } catch (error) {
                    console.error('Failed to sync offline sale', error);
                    this.message = {
                        type: 'error',
                        text: 'حدث خطأ أثناء مزامنة بعض طلبات الـ Offline.',
                    };
                    return;
                }
            }

            this.offlineQueue = [];
            this.persistOfflineQueue();

            this.message = {
                type: 'success',
                text: 'تمت مزامنة كل الطلبات الـ Offline بنجاح.',
            };
        },

        get total() {
            return (this.cart || []).reduce((sum, item) => {
                const qty = Number(item.qty ?? 0);
                const price = Number(item.price ?? 0);
                return sum + qty * price;
            }, 0);
        },

        clearMessage() {
            this.message = null;
        },

        async fetchProducts() {
            // Validation: minimum search length
            if (!this.search || this.search.length < 2) {
                this.products = [];
                return;
            }

            // Check online status
            if (!window.navigator.onLine) {
                this.message = {
                    type: 'info',
                    text: 'الاتصال غير متاح، لا يمكن البحث عن المنتجات الآن.',
                };
                return;
            }

            // Prevent concurrent searches
            if (this.isSearching) {
                return;
            }

            this.isSearching = true;
            this.products = [];
            this.clearMessage();

            try {
                const response = await window.axios.get(`/api/v1/branches/${this.branchId}/products/search`, {
                    params: { q: this.search },
                    timeout: 10000, // 10 second timeout
                });

                let data = response.data;

                // Handle standardized API response format
                if (data && data.success === true && Array.isArray(data.data)) {
                    this.products = data.data;
                } else if (Array.isArray(data)) {
                    // Fallback for direct array response
                    this.products = data;
                } else if (data && Array.isArray(data.data)) {
                    // Fallback for nested data
                    this.products = data.data;
                } else {
                    this.products = [];
                    console.warn('Unexpected API response format:', data);
                }
            } catch (error) {
                console.error('POS search error', error);
                
                // Handle specific error types
                let errorMessage = 'حدث خطأ أثناء البحث عن المنتجات.';
                if (error.code === 'ECONNABORTED') {
                    errorMessage = 'انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.';
                } else if (error.response) {
                    // Server responded with error
                    if (error.response.status === 401) {
                        errorMessage = 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.';
                    } else if (error.response.status === 403) {
                        errorMessage = 'ليس لديك صلاحية للبحث عن المنتجات.';
                    } else if (error.response.status >= 500) {
                        errorMessage = 'خطأ في الخادم. يرجى المحاولة لاحقاً.';
                    } else if (error.response.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                } else if (error.request) {
                    // Request made but no response
                    errorMessage = 'لا يمكن الاتصال بالخادم. يرجى التحقق من الاتصال.';
                }

                this.message = {
                    type: 'error',
                    text: errorMessage,
                };
            } finally {
                this.isSearching = false;
            }
        },

        addProduct(product) {
            if (!product) {
                return;
            }

            const id = product.id ?? product.product_id;
            if (!id) {
                return;
            }

            const existingIndex = this.cart.findIndex((item) => item.product_id === id);

            if (existingIndex !== -1) {
                this.cart[existingIndex].qty = Number(this.cart[existingIndex].qty ?? 0) + 1;
            } else {
                this.cart.push({
                    product_id: id,
                    name: product.name ?? product.label ?? 'Item',
                    qty: 1,
                    price: Number(product.price ?? product.sale_price ?? 0),
                    discount: 0,
                    percent: false,
                    tax_id: product.tax_id ?? null,
                });
            }

            this.persistCart();
        },

        removeItem(index) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            this.cart.splice(index, 1);
            this.persistCart();
        },

        updateQty(index, qty) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            
            // Parse and validate quantity
            const value = Number(qty ?? 0);
            
            // Ensure positive quantity, minimum 0.01, maximum 999999
            if (isNaN(value) || value <= 0) {
                this.cart[index].qty = 1;
            } else if (value > 999999) {
                this.cart[index].qty = 999999;
                this.message = {
                    type: 'warning',
                    text: 'الكمية القصوى المسموح بها هي 999999.',
                };
            } else {
                this.cart[index].qty = value;
            }
            
            this.persistCart();
        },

        updatePrice(index, price) {
            if (index < 0 || index >= this.cart.length) {
                return;
            }
            
            // Parse and validate price
            const value = Number(price ?? 0);
            
            // Ensure non-negative price, maximum 9999999.99
            if (isNaN(value) || value < 0) {
                this.cart[index].price = 0;
            } else if (value > 9999999.99) {
                this.cart[index].price = 9999999.99;
                this.message = {
                    type: 'warning',
                    text: 'السعر القصوى المسموح به هو 9999999.99.',
                };
            } else {
                // Round to 2 decimal places
                this.cart[index].price = Math.round(value * 100) / 100;
            }
            
            this.persistCart();
        },

        enqueueOfflineSale(payload) {
            if (!this.offlineQueue) {
                this.offlineQueue = [];
            }
            this.offlineQueue.push({
                payload,
                queued_at: new Date().toISOString(),
            });
            this.persistOfflineQueue();
        },

        async checkout() {
            // Validation: check cart has items
            if (!this.cart.length) {
                this.message = {
                    type: 'info',
                    text: 'لا توجد عناصر في السلة.',
                };
                return;
            }

            // Prevent double submit
            if (this.isCheckingOut) {
                return;
            }

            // Validate cart items
            const invalidItems = this.cart.filter(item => !item.product_id || Number(item.qty) <= 0);
            if (invalidItems.length > 0) {
                this.message = {
                    type: 'error',
                    text: 'بعض العناصر في السلة غير صالحة. يرجى التحقق من الكميات.',
                };
                return;
            }

            const items = this.cart.map((item) => ({
                product_id: item.product_id,
                qty: Number(item.qty ?? 1),
                price: Number(item.price ?? 0),
                discount: Number(item.discount ?? 0),
                percent: !!item.percent,
                tax_id: item.tax_id ?? null,
            }));

            const payload = {
                items,
            };

            // Offline: enqueue and clear cart
            if (this.offline) {
                this.enqueueOfflineSale(payload);
                this.cart = [];
                this.persistCart();
                this.message = {
                    type: 'info',
                    text: 'تم حفظ الطلب في وضع عدم الاتصال. سيتم مزامنته عند توفر الإنترنت.',
                };
                return;
            }

            this.isCheckingOut = true;
            this.clearMessage();

            try {
                const response = await window.axios.post(`/api/v1/branches/${this.branchId}/pos/checkout`, payload, {
                    timeout: 15000, // 15 second timeout for checkout
                });

                // Extract message from standardized API response
                const data = response.data ?? {};
                let msg = 'تم تنفيذ عملية البيع بنجاح.';
                
                if (data.success === true && data.message) {
                    msg = data.message;
                } else if (data.message) {
                    msg = data.message;
                } else if (data.status) {
                    msg = data.status;
                }

                // Clear cart on success
                this.cart = [];
                this.persistCart();

                this.message = {
                    type: 'success',
                    text: msg,
                };

                // Optional: Show sale details if available
                if (data.data && data.data.code) {
                    console.log('Sale created:', data.data.code);
                }
            } catch (error) {
                console.error('POS checkout error', error);
                
                // Handle specific error types
                let errorMessage = 'فشل تنفيذ عملية البيع.';
                if (error.code === 'ECONNABORTED') {
                    errorMessage = 'انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.';
                } else if (error.response) {
                    // Server responded with error
                    if (error.response.status === 401) {
                        errorMessage = 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.';
                    } else if (error.response.status === 403) {
                        errorMessage = 'ليس لديك صلاحية لإجراء عمليات البيع.';
                    } else if (error.response.status === 422) {
                        // Validation error
                        if (error.response.data?.errors) {
                            const errors = Object.values(error.response.data.errors).flat();
                            errorMessage = errors.join(' ');
                        } else if (error.response.data?.message) {
                            errorMessage = error.response.data.message;
                        }
                    } else if (error.response.status >= 500) {
                        errorMessage = 'خطأ في الخادم. يرجى المحاولة لاحقاً.';
                    } else if (error.response.data?.message) {
                        errorMessage = error.response.data.message;
                    }
                } else if (error.request) {
                    // Request made but no response
                    errorMessage = 'لا يمكن الاتصال بالخادم. يرجى التحقق من الاتصال.';
                }

                this.message = {
                    type: 'error',
                    text: errorMessage,
                };
            } finally {
                this.isCheckingOut = false;
            }
        },
    };
}

window.erpPosTerminal = erpPosTerminal;
