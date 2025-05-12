/**
 * Main script file for general site interactions.
 * Handles mobile menu toggle, footer toggle, category/product filtering,
 * cart management (add, update, remove, clear), toast notifications,
 * confirmation modals (generic and order cancellation), and reusable quantity controls.
 */
/**
 * Retrieves the CSRF token from the meta tag.
 * @returns {string|null} The CSRF token or null if not found.
 */
function getCsrfToken() {
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    if (tokenElement) {
        return tokenElement.getAttribute('content');
    }
    console.error('CSRF token meta tag not found.');
    return null; // Or an empty string, depending on how you want to handle missing token
}
// Optionally, make it available on the window object if needed by other separate scripts,
// though direct function call should work if script.js is loaded first.
// window.getCsrfToken = getCsrfToken;
document.addEventListener('DOMContentLoaded', function () {

    // --- Homepage Search Loading Indicator ---
    const homepageSearchForm = document.getElementById('homepage-search-form');
    const homepageSearchButton = document.getElementById('homepage-search-button');

    if (homepageSearchForm && homepageSearchButton) {
        homepageSearchForm.addEventListener('submit', function () {
            // Apply loading state to the button
            homepageSearchButton.classList.add('loading');
            homepageSearchButton.disabled = true;

            // Form will naturally submit (no preventDefault)
        });
    }


    // --- Footer Toggle ---
    const footerToggle = document.querySelector('.footer-toggle');
    const footerToggleIcon = document.getElementById('footer-toggle-icon');
    const footer = document.getElementById('site-footer');
    if (footerToggle && footer && footerToggleIcon) { // Ensure all elements exist
        footerToggle.addEventListener('click', function () {
            // Toggles the visibility of the site footer
            footer.classList.toggle('hidden');
            // Rotates the toggle icon
            footerToggleIcon.classList.toggle('rotate');

            // If the footer is being shown, scroll to the bottom after a short delay
            if (!footer.classList.contains('hidden')) {
                setTimeout(function () {
                    try {
                        // Smooth scroll if supported
                        window.scrollTo({
                            top: document.body.scrollHeight,
                            behavior: 'smooth'
                        });
                    } catch (e) {
                        // Fallback for older browsers
                        window.scrollTo(0, document.body.scrollHeight);
                    }
                }, 500); // Delay allows footer animation to start
            }
        });
    } else {
        if (!footerToggle) console.warn("Footer toggle button not found.");
        if (!footerToggleIcon) console.warn("Footer toggle icon not found.");
        if (!footer) console.warn("Site footer element not found.");
    }

    // --- Generic Confirmation Modal ---
    let currentModalConfirmCallback = null; // Stores the callback function for the current confirmation
    const modalConfirmButton = document.getElementById('modal-confirm-button');
    const modalCancelButton = document.getElementById('modal-cancel-button');
    const confirmationModal = document.getElementById('confirmation-modal');
    const modalMessage = document.getElementById('modal-message'); // Get message element

    // Setup persistent listener for the Confirm button
    if (modalConfirmButton && confirmationModal) {
        modalConfirmButton.addEventListener('click', () => {
            console.log('Persistent Confirm Listener Fired.');
            // Execute the stored callback if it's a valid function
            if (typeof currentModalConfirmCallback === 'function') {
                console.log('Executing stored callback.');
                try {
                    currentModalConfirmCallback();
                } catch (error) {
                    console.error("Error executing modal confirm callback:", error);
                    showToast("An error occurred during the confirmation action.", "error");
                }
            } else {
                console.log('No callback stored or callback is not a function.');
            }
            // Clear the callback and hide the modal
            currentModalConfirmCallback = null;
            confirmationModal.classList.remove('modal-visible');
        });
    } else {
        if (!modalConfirmButton) console.warn("Modal confirm button (#modal-confirm-button) not found for persistent listener setup.");
        if (!confirmationModal) console.warn("Confirmation modal (#confirmation-modal) not found for persistent listener setup.");
    }

    // Setup persistent listener for the Cancel button
    if (modalCancelButton && confirmationModal) {
        modalCancelButton.addEventListener('click', () => {
            console.log('Persistent Cancel Listener Fired.');
            // Clear the callback and hide the modal
            currentModalConfirmCallback = null;
            confirmationModal.classList.remove('modal-visible');
        });
    } else {
        if (!modalCancelButton) console.warn("Modal cancel button (#modal-cancel-button) not found for persistent listener setup.");
        if (!confirmationModal) console.warn("Confirmation modal (#confirmation-modal) not found for persistent listener setup.");
    }

    /**
     * Displays the generic confirmation modal with a specific message and callback.
     * @param {string} message - The message to display in the modal.
     * @param {function} confirmCallback - The function to execute when the confirm button is clicked.
     * @param {function} [cancelCallback] - Optional function to execute when cancel button is clicked or modal is dismissed.
     */
    function showConfirmationModal(message, confirmCallback, cancelCallback) {
        console.log('Showing confirmation modal. Message:', message);
        if (confirmationModal && modalMessage) {
            modalMessage.textContent = message; // Set the message text
            currentModalConfirmCallback = confirmCallback; // Store the callback
            console.log('Callback stored. Type:', typeof currentModalConfirmCallback);

            // Enhance cancel button to also call cancelCallback if provided
            const enhancedCancelHandler = () => {
                if (typeof cancelCallback === 'function') {
                    try {
                        cancelCallback();
                    } catch (error) {
                        console.error("Error executing modal cancel callback:", error);
                    }
                }
                confirmationModal.classList.remove('modal-visible');
                currentModalConfirmCallback = null; // Clear confirm callback too
                // Remove this specific listener to avoid stacking if modal is reused
                modalCancelButton.removeEventListener('click', enhancedCancelHandler);
            };
            // Remove any old listener before adding a new one for cancel
            // This is a bit tricky if the original listener is anonymous.
            // For simplicity, we assume the persistent one is okay, or we manage it carefully.
            // Let's assume the persistent one handles basic hide, and this adds to it.
            // A better way would be a single, more complex persistent cancel handler.
            // For now, let's ensure the cancelCallback is called.
            // The persistent cancel listener already handles hiding and clearing currentModalConfirmCallback.
            // We just need to ensure cancelCallback is invoked.
            // So, we'll modify the persistent confirm and cancel.
            // This is getting complex. Let's simplify: the `showConfirmationModal` will set a temporary cancel callback.

            // Re-assigning the persistent listener's callback is not ideal.
            // Instead, the persistent listener for cancel should check for a temporary cancel callback.
            // Let's store cancelCallback similarly to currentModalConfirmCallback.
            // This requires modifying the persistent cancel listener.
            // For now, let's stick to the provided structure and call cancelCallback from here if modal is dismissed.
            // This is tricky. The original persistent cancel listener will fire.
            // We need a way for *that* listener to know about *this* specific cancelCallback.

            // Simpler approach for now: The `confirmCallback` is primary.
            // If the user cancels, the generic cancel handler (lines 102-112) runs.
            // If a specific cancel action is needed (like resetting an input),
            // it should be passed to `showConfirmationModal` and handled.

            // Let's make the persistent cancel listener smarter.
            // We'll add a temporary reference for the specific cancel action.
            confirmationModal._currentCancelCallback = cancelCallback; // Store temporary cancel callback

            confirmationModal.classList.add('modal-visible'); // Show the modal
            console.log('Added "modal-visible" class. Modal classList:', confirmationModal.classList);
        } else {
            console.error('Modal (#confirmation-modal) or message element (#modal-message) not found in showConfirmationModal.');
            if (!confirmationModal) console.error("Confirmation modal element is missing.");
            if (!modalMessage) console.error("Modal message element is missing.");
        }
    }

    // Modified persistent listener for the Cancel button to handle specific cancel callbacks
    if (modalCancelButton && confirmationModal) {
        modalCancelButton.addEventListener('click', () => {
            console.log('Persistent Cancel Listener Fired.');
            if (typeof confirmationModal._currentCancelCallback === 'function') {
                try {
                    confirmationModal._currentCancelCallback();
                } catch (error) {
                    console.error("Error executing specific modal cancel callback:", error);
                }
            }
            // Clear callbacks and hide the modal
            currentModalConfirmCallback = null;
            confirmationModal._currentCancelCallback = null;
            confirmationModal.classList.remove('modal-visible');
        });
    }

    // --- Category and Product Filtering ---
    const mainCategorySelect = document.getElementById('main-category');
    const subCategorySelect = document.getElementById('sub-category');
    const productDisplayArea = document.getElementById('product-display-area');

    /**
     * Resets the sub-category dropdown to its default state (disabled, placeholder text).
     */
    function resetSubCategoryDropdown() {
        if (subCategorySelect) {
            subCategorySelect.innerHTML = '<option value="">-- Select Sub-category --</option>';
            subCategorySelect.disabled = true;
        }
    }

    // Initial reset on page load
    resetSubCategoryDropdown();

    // Event listener for main category selection change
    if (mainCategorySelect) {
        mainCategorySelect.addEventListener('change', function () {
            const categoryId = this.value; // Get selected main category ID
            resetSubCategoryDropdown(); // Reset sub-categories first

            // Update product display area based on selection
            if (productDisplayArea) {
                productDisplayArea.innerHTML = '<p>Loading products...</p>'; // Show loading message
                // If 'all' or no category selected, show prompt
                if (categoryId === 'all' || !categoryId) {
                    productDisplayArea.innerHTML = '<p>Select a specific category to view products.</p>';
                    return; // Stop further processing
                }
            } else {
                // If no product display area, still check if we need to fetch subcategories
                if (categoryId === 'all' || !categoryId) {
                    return; // Stop if 'all' or no category selected
                }
            }

            // Fetch sub-categories for the selected main category
            fetchAndPopulateSubcategories(categoryId);

            // Fetch and render products for the selected main category (if display area exists)
            if (productDisplayArea) {
                fetch(`/ajax/products-by-category?categoryId=${encodeURIComponent(categoryId)}`)
                    .then(response => {
                        // Check for HTTP errors
                        if (!response.ok) {
                            throw new Error(`Product fetch HTTP error! status: ${response.status}`);
                        }
                        // Check if the response is JSON
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            // Throw error if not JSON
                            return response.text().then(text => { throw new Error("Expected JSON for products, got: " + text); });
                        }
                    })
                    .then(data => {
                        // Handle potential server-side errors in the JSON response
                        if (data.error) {
                            console.error('Server error loading products:', data.error);
                            productDisplayArea.innerHTML = `<p>Error loading products: ${escapeHTML(data.error)}</p>`;
                        } else if (data.products && Array.isArray(data.products)) {
                            // Render products if data is valid
                            renderProducts(data.products);
                        } else {
                            // Handle unexpected data format
                            console.error('Unexpected product data format:', data);
                            productDisplayArea.innerHTML = '<p>Could not load products. Unexpected data format received.</p>';
                        }
                    })
                    .catch(error => {
                        // Handle fetch errors (network issues, etc.)
                        console.error('Fetch products error:', error);
                        productDisplayArea.innerHTML = `<p>Error loading products. Please check connection. ${escapeHTML(error.message)}</p>`;
                    });
            }
        });
    }

    // Event listener for sub-category selection change
    if (subCategorySelect) {
        subCategorySelect.addEventListener('change', function () {
            const subCategoryId = this.value; // Get selected sub-category ID

            // Update product display area based on selection
            if (productDisplayArea) {
                productDisplayArea.innerHTML = '<p>Loading products...</p>'; // Show loading message
                // Fetch products only if a valid sub-category is selected
                if (subCategoryId && subCategoryId !== "") {
                    fetch(`/ajax/products-by-category?categoryId=${encodeURIComponent(subCategoryId)}`)
                        .then(response => {
                            // Check for HTTP errors
                            if (!response.ok) {
                                throw new Error(`Sub-category Product fetch HTTP error! status: ${response.status}`);
                            }
                            // Check if the response is JSON
                            const contentType = response.headers.get("content-type");
                            if (contentType && contentType.indexOf("application/json") !== -1) {
                                return response.json();
                            } else {
                                // Throw error if not JSON
                                return response.text().then(text => { throw new Error("Expected JSON for sub-category products, got: " + text); });
                            }
                        })
                        .then(data => {
                            // Handle potential server-side errors
                            if (data.error) {
                                console.error('Server error loading sub-category products:', data.error);
                                productDisplayArea.innerHTML = `<p>Error loading products: ${escapeHTML(data.error)}</p>`;
                            } else if (data.products && Array.isArray(data.products)) {
                                // Render products if data is valid
                                renderProducts(data.products);
                            } else {
                                // Handle unexpected data format
                                console.error('Unexpected sub-category product data format:', data);
                                productDisplayArea.innerHTML = '<p>Could not load products. Unexpected data format received.</p>';
                            }
                        })
                        .catch(error => {
                            // Handle fetch errors
                            console.error('Fetch sub-category products error:', error);
                            productDisplayArea.innerHTML = `<p>Error loading products. Please check connection. ${escapeHTML(error.message)}</p>`;
                        });
                } else {
                    // If no sub-category selected, show prompt
                    productDisplayArea.innerHTML = '<p>Select a sub-category to view products.</p>';
                }
            }
        });
    }

    /**
     * Fetches sub-categories based on a parent category ID and populates the sub-category dropdown.
     * @param {string|number} categoryId - The ID of the parent category.
     */
    function fetchAndPopulateSubcategories(categoryId, activeSubCategoryId = null) {
        // Do nothing if the sub-category select element doesn't exist
        if (!subCategorySelect) {
            return Promise.resolve(); // Return a resolved promise if no select element
        }
        // Reset the dropdown before fetching
        resetSubCategoryDropdown();

        // Do nothing if no valid category ID is provided
        if (!categoryId || categoryId === 'all') {
            return Promise.resolve(); // Return a resolved promise
        }

        // Fetch sub-categories from the server
        return fetch(`/ajax/subcategories?parentId=${encodeURIComponent(categoryId)}`)
            .then(response => {
                // Check for HTTP errors
                if (!response.ok) {
                    throw new Error(`Subcategory fetch HTTP error! status: ${response.status}`);
                }
                // Check if the response is JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    // Throw error if not JSON
                    return response.text().then(text => { throw new Error("Expected JSON for subcategories, got: " + text); });
                }
            })
            .then(data => {
                // Handle potential server-side errors
                if (data.error) {
                    console.error('Server error fetching subcategories:', data.error);
                    subCategorySelect.innerHTML = '<option value="">-- Error Loading --</option>';
                } else if (data.subcategories && Array.isArray(data.subcategories)) {
                    // If sub-categories are found
                    if (data.subcategories.length > 0) {
                        let subCategorySelected = false;
                        // Populate the dropdown with options
                        data.subcategories.forEach(subCat => {
                            // Basic validation of subcategory data
                            if (subCat && subCat.category_id && subCat.category_name) {
                                const option = document.createElement('option');
                                option.value = subCat.category_id;
                                option.textContent = subCat.category_name;
                                if (activeSubCategoryId && parseInt(subCat.category_id, 10) === parseInt(activeSubCategoryId, 10)) {
                                    option.selected = true;
                                    subCategorySelected = true;
                                }
                                subCategorySelect.appendChild(option);
                            } else {
                                console.warn('Skipping invalid subcategory data:', subCat);
                            }
                        });
                        subCategorySelect.disabled = false; // Enable the dropdown

                        // If a subcategory was programmatically selected, trigger change to load its products
                        if (subCategorySelected) {
                            console.log(`Active sub-category ${activeSubCategoryId} selected. Triggering change.`);
                            subCategorySelect.dispatchEvent(new Event('change'));
                        }
                    } else {
                        // If no sub-categories found
                        subCategorySelect.innerHTML = '<option value="">-- No Sub-categories --</option>';
                    }
                } else {
                    // Handle unexpected data format
                    console.error('Unexpected data format for subcategories:', data);
                    subCategorySelect.innerHTML = '<option value="">-- Bad Format --</option>';
                }
            })
            .catch(error => {
                // Handle fetch errors
                console.error('Fetch subcategories error:', error);
                subCategorySelect.innerHTML = `<option value="">-- Load Failed --</option>`;
                throw error; // Re-throw to be caught by initial load logic if needed
            });
    }

    /**
     * Renders a list of products into the product display area.
     * @param {Array<object>} products - An array of product objects. Each object should have
     *                                   product_id, name, price, and image_path properties.
     */
    function renderProducts(products) {
        // Do nothing if the display area doesn't exist
        if (!productDisplayArea) {
            console.warn("Attempted to render products, but #product-display-area not found.");
            return;
        }
        // Show message if no products are provided
        if (!products || products.length === 0) {
            productDisplayArea.innerHTML = '<p>No products found in this category.</p>';
            return;
        }

        let html = '';
        const productsPerRow = 4; // Number of products per row

        // Iterate through products in chunks based on productsPerRow
        for (let i = 0; i < products.length; i += productsPerRow) {
            html += '<div class="products-row">'; // Start a new row
            const rowProducts = products.slice(i, i + productsPerRow); // Get products for this row

            // Generate HTML for each product in the row
            rowProducts.forEach(prod => {
                // Basic validation of product data
                if (!prod || typeof prod !== 'object' || !prod.product_id || typeof prod.name === 'undefined' || typeof prod.price === 'undefined' || typeof prod.image_path === 'undefined') {
                    console.warn('Skipping invalid product data:', prod);
                    return; // Skip this product
                }

                // Check if the user is logged in (used to show Add to Cart or Login button)
                const isLoggedIn = document.body.getAttribute('data-logged-in') === 'true';
                const price = parseFloat(prod.price);
                const formattedPrice = !isNaN(price) ? price.toFixed(2) : 'N/A'; // Format price

                // Generate product card HTML
                html += `
                    <div class="product-card">
                        <a href="/product/${escapeHTML(prod.product_id)}/${escapeHTML(prod.slug)}" class="product-link">
                            <img src="/${escapeHTML(prod.image_path)}" alt="${escapeHTML(prod.name)}" class="product-image">
                            <h4 class="product-name">${escapeHTML(prod.name)}</h4>
                            <p class="product-price">$${formattedPrice}</p>
                        </a>
                        ${isLoggedIn
                        ? `<button class="add-to-cart-btn" data-product-id="${escapeHTML(prod.product_id)}">Add to Cart</button>` // Show Add to Cart if logged in
                        : `<a href="/login" class="login-to-purchase-btn">Login to Purchase</a>` // Show Login link if not logged in
                    }
                    </div>
                `;
            });
            html += '</div>'; // End the row
        }
        // Update the display area with the generated HTML
        productDisplayArea.innerHTML = html;
    }

    /**
     * Escapes HTML special characters in a string to prevent XSS.
     * Handles null, undefined, and non-string/number/boolean inputs gracefully.
     * @param {string|number|boolean|null|undefined} str - The input value to escape.
     * @returns {string} The escaped HTML string.
     */
    function escapeHTML(str) {
        // Return empty string for null or undefined
        if (str === null || str === undefined) return '';
        // Warn and convert non-primitive types to string
        if (typeof str !== 'string' && typeof str !== 'number' && typeof str !== 'boolean') {
            console.warn("Attempted to escape non-primitive value:", str);
            str = String(str);
        }
        // Use browser's text node creation for safe escaping
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str))); // Ensure it's a string
        return div.innerHTML;
    }


    // --- Toast Notifications ---

    /**
     * Displays a toast notification message.
     * @param {string} message - The message to display.
     * @param {'success'|'error'|'info'} [type='success'] - The type of toast (affects styling and icon).
     * @param {number} [duration=3000] - How long the toast should be visible in milliseconds.
     */
    function showToast(message, type = 'success', duration = 3000) {
        const toastContainer = document.getElementById('toast-container');
        // Do nothing if the container element doesn't exist
        if (!toastContainer) {
            console.error('Toast container (#toast-container) not found');
            return;
        }

        // Create the toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`; // Apply base and type-specific classes

        // Determine the Font Awesome icon based on the type
        let iconClass = 'fa-check-circle'; // Default to success
        if (type === 'error') {
            iconClass = 'fa-exclamation-circle';
        } else if (type === 'info') {
            iconClass = 'fa-info-circle';
        }

        // Set the inner HTML with icon and message (escaped)
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${iconClass}"></i>
            </div>
            <div class="toast-content">
                ${escapeHTML(message)}
            </div>
        `;

        // Add the toast to the container
        toastContainer.appendChild(toast);

        // Trigger the entrance animation shortly after adding to DOM
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        // Set timeout to remove the toast after the specified duration
        setTimeout(() => {
            toast.classList.add('toast-exit'); // Add exit class for animation
            // Remove the element from DOM after the exit animation completes
            setTimeout(() => {
                if (toast.parentNode) { // Check if it hasn't already been removed
                    toast.parentNode.removeChild(toast);
                }
            }, 300); // Matches the duration of the CSS exit animation
        }, duration);
    }

    // --- Reusable Quantity Controls ---
    /**
     * Initializes a set of quantity controls (increase, decrease, input).
     * @param {HTMLElement} containerElement - The DOM element containing the quantity controls.
     * @param {object} options - Configuration options.
     * @param {string} options.productId - The ID of the product.
     * @param {number} [options.minQuantity=1] - Minimum allowed quantity.
     * @param {number} [options.maxQuantity=99] - Maximum allowed quantity.
     * @param {function} [options.onQuantityChange] - Callback: (productId, newQuantity) => void.
     * @param {function} [options.onQuantityZero] - Callback: (productId, inputElement, previousValue) => void.
     */
    function initializeQuantityControls(containerElement, options) {
        const quantityInput = containerElement.querySelector('.quantity-input');
        const decreaseBtn = containerElement.querySelector('.decrease-btn');
        const increaseBtn = containerElement.querySelector('.increase-btn');

        if (!quantityInput || !decreaseBtn || !increaseBtn) {
            console.error('Quantity control elements not found in container:', containerElement);
            return;
        }

        const productId = options.productId;
        const minQty = options.minQuantity !== undefined ? parseInt(options.minQuantity, 10) : (parseInt(quantityInput.min, 10) || 1);
        const maxQty = options.maxQuantity !== undefined ? parseInt(options.maxQuantity, 10) : (parseInt(quantityInput.max, 10) || 99);

        // Ensure minQty is not less than 1 for general use, unless explicitly set lower and handled by callbacks
        const effectiveMinQty = Math.max(1, minQty);

        // Store initial value for potential revert by onQuantityZero callback
        quantityInput.dataset.previousValue = quantityInput.value;

        increaseBtn.addEventListener('click', function () {
            let currentValue = parseInt(quantityInput.value, 10);
            if (isNaN(currentValue)) currentValue = effectiveMinQty;

            const newValue = Math.min(maxQty, currentValue + 1);
            if (newValue !== currentValue) {
                quantityInput.value = newValue;
                quantityInput.dataset.previousValue = newValue.toString(); // Update previous value
                if (options.onQuantityChange) {
                    options.onQuantityChange(productId, newValue);
                }
            }
        });

        decreaseBtn.addEventListener('click', function () {
            let currentValue = parseInt(quantityInput.value, 10);
            if (isNaN(currentValue)) currentValue = effectiveMinQty;

            const newValue = currentValue - 1;

            if (newValue < effectiveMinQty) { // Handles 0 or less, or below a specific minQty if it was > 1
                if (options.onQuantityZero) {
                    // Pass the value that was about to be set (e.g., 0)
                    options.onQuantityZero(productId, quantityInput, currentValue.toString()); // Pass current valid value as previous
                } else {
                    // Default behavior if no onQuantityZero: clamp to minQty
                    quantityInput.value = effectiveMinQty;
                    quantityInput.dataset.previousValue = effectiveMinQty.toString();
                    if (options.onQuantityChange && effectiveMinQty !== currentValue) { // Only call if changed
                        options.onQuantityChange(productId, effectiveMinQty);
                    }
                }
            } else {
                quantityInput.value = newValue;
                quantityInput.dataset.previousValue = newValue.toString();
                if (options.onQuantityChange) {
                    options.onQuantityChange(productId, newValue);
                }
            }
        });

        quantityInput.addEventListener('change', function () {
            let currentValue = parseInt(quantityInput.value, 10);
            const previousValue = quantityInput.dataset.previousValue || effectiveMinQty.toString();

            if (isNaN(currentValue)) {
                currentValue = effectiveMinQty;
                showToast(`Quantity must be a valid number. Set to ${effectiveMinQty}.`, 'info');
            }

            if (currentValue < effectiveMinQty) {
                if (options.onQuantityZero) {
                    options.onQuantityZero(productId, quantityInput, previousValue);
                    // onQuantityZero is responsible for the final input value or action
                } else {
                    quantityInput.value = effectiveMinQty;
                    showToast(`Minimum quantity is ${effectiveMinQty}.`, 'info');
                    if (options.onQuantityChange && quantityInput.value !== previousValue) {
                        options.onQuantityChange(productId, effectiveMinQty);
                    }
                }
            } else if (currentValue > maxQty) {
                quantityInput.value = maxQty;
                showToast(`Maximum quantity is ${maxQty}.`, 'info');
                if (options.onQuantityChange && quantityInput.value !== previousValue) {
                    options.onQuantityChange(productId, maxQty);
                }
            } else {
                // Valid quantity within bounds (or handled by onQuantityZero if it was < effectiveMinQty)
                if (options.onQuantityChange && currentValue.toString() !== previousValue) {
                    options.onQuantityChange(productId, currentValue);
                }
            }
            quantityInput.dataset.previousValue = quantityInput.value; // Update after all changes
        });

        // Initial check for stock limits (e.g. product detail page)
        if (maxQty === 0 || maxQty < effectiveMinQty) {
            const form = containerElement.closest('form');
            if (form) {
                const addToCartBtn = form.querySelector('.add-to-cart-btn-detail');
                if (addToCartBtn) addToCartBtn.disabled = true;
            }
            quantityInput.disabled = true;
            decreaseBtn.disabled = true;
            increaseBtn.disabled = true;
        }
    }

    // --- Cart Management ---

    // Global event listener for adding items to the cart (delegated)
    document.addEventListener('click', function (event) {
        // Check if the clicked element is an "Add to Cart" button
        if (event.target.matches('.add-to-cart-btn') || event.target.matches('.add-to-cart-btn-detail')) {
            event.preventDefault(); // Prevent default button action if any
            const button = event.target;
            const productId = button.getAttribute('data-product-id');

            // Validate product ID
            if (!productId) {
                console.error('Product ID not found on button.');
                showToast('Could not add item: Product ID missing.', 'error');
                return;
            }

            // Provide visual feedback and disable button during request
            button.textContent = 'Adding...';
            button.disabled = true;

            // Prepare data for API request
            let quantity = 1;
            let csrfToken = '';

            if (button.classList.contains('add-to-cart-btn-detail')) {
                const form = button.closest('.add-to-cart-form-detail');
                if (form) {
                    const quantityInput = form.querySelector('.quantity-input');
                    const csrfInput = form.querySelector('input[name="csrf_token"]');
                    if (quantityInput) {
                        quantity = parseInt(quantityInput.value, 10) || 1;
                    }
                    if (csrfInput) {
                        csrfToken = csrfInput.value;
                    }
                }
            }

            const data = {
                product_id: productId,
                quantity: quantity
            };

            if (csrfToken) {
                data.csrf_token = csrfToken;
            } else {
                // For generic add to cart buttons, try to get CSRF token using the helper
                data.csrf_token = getCsrfToken();
            }

            // Send request to the add-to-cart API endpoint
            fetch('/api/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json' // Expect JSON response
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    // Check response status and content type
                    const contentType = response.headers.get("content-type");
                    if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json(); // Parse JSON if successful
                    } else if (!response.ok) {
                        if (response.status === 403) {
                            showToast("Your session may have expired or the request was tampered with. Please refresh the page and try again.", "error");
                            // Re-enable button and reset text, then throw to prevent further processing
                            button.textContent = button.classList.contains('add-to-cart-btn-detail') ? 'Add to Cart' : 'Add to Cart'; // Keep original text based on class
                            button.disabled = false;
                            return Promise.reject(new Error('CSRF validation failed (403)')); // Stop further .then()
                        }
                        // Handle other HTTP errors by reading the response text
                        return response.text().then(text => {
                            throw new Error(`Add to cart failed: ${response.status} ${response.statusText}. ${text}`);
                        });
                    } else {
                        // Handle cases where response is OK but not JSON
                        return response.text().then(text => { throw new Error("Expected JSON response from add to cart, got non-JSON: " + text); });
                    }
                })
                .then(data => {
                    // Re-enable button and reset text
                    button.textContent = button.classList.contains('add-to-cart-btn-detail') ? 'Add to Cart' : 'Add to Cart'; // Keep original text
                    button.disabled = false;
                    // Handle API response (success or error)
                    if (data.success) {
                        showToast(data.message || 'Item added to cart successfully!', 'success');
                        updateCartIcon(true); // Update cart icon to filled state
                        updateCartBadge(); // Update the item count badge
                    } else {
                        showToast('Error: ' + (data.message || 'Could not add item to cart.'), 'error');
                    }
                })
                .catch(error => {
                    // Handle fetch errors (network, etc.)
                    // Check if the error is the one we threw for 403, if so, message is already shown
                    if (error.message !== 'CSRF validation failed (403)') {
                        console.error('Add to Cart Fetch error:', error);
                        showToast(`An error occurred: ${error.message}. Please try again.`, 'error');
                    }
                    // Re-enable button and reset text (already handled for 403, but good for other errors)
                    if (!button.disabled) { // Avoid resetting if already handled by 403 block
                        button.textContent = button.classList.contains('add-to-cart-btn-detail') ? 'Add to Cart' : 'Add to Cart';
                        button.disabled = false;
                    }
                });
        }
    });


    // Event listeners specifically for the cart page (.cart-container)
    const cartContainer = document.querySelector('.cart-container');
    if (cartContainer) {

        function setupCartQuantityControls() {
            const quantityControlElements = cartContainer.querySelectorAll('.quantity-controls');
            quantityControlElements.forEach(controlsContainer => {
                const quantityInput = controlsContainer.querySelector('.quantity-input');
                if (!quantityInput) return;

                const productId = quantityInput.dataset.productId;
                if (!productId) return;

                initializeQuantityControls(controlsContainer, {
                    productId: productId,
                    minQuantity: 1, // Cart items usually have a min of 1 before removal
                    maxQuantity: 99, // Default max for cart items
                    onQuantityChange: (pid, newQuantity) => {
                        updateCartItemQuantity(pid, newQuantity);
                    },
                    onQuantityZero: (pid, inputEl, previousVal) => {
                        showConfirmationModal(
                            'Are you sure you want to remove this item from your cart?',
                            function () { // Confirm callback
                                removeCartItem(pid);
                            },
                            function () { // Cancel callback
                                // Restore previous quantity if removal is cancelled
                                inputEl.value = previousVal; // Use the passed previous value
                                inputEl.dataset.previousValue = previousVal;
                                // No AJAX update needed as it's a revert to a known state
                            }
                        );
                    }
                });
            });
        }

        setupCartQuantityControls(); // Initial setup

        // Listener for button clicks within the cart (remove, clear)
        // Increase/Decrease are handled by initializeQuantityControls
        cartContainer.addEventListener('click', function (event) {
            // --- Remove Item Button ---
            // Handles clicks directly on the button or its icon/children
            if (event.target.matches('.remove-item-btn') || event.target.closest('.remove-item-btn')) {
                event.preventDefault();
                const button = event.target.matches('.remove-item-btn') ? event.target : event.target.closest('.remove-item-btn');
                const productId = button.getAttribute('data-product-id');
                console.log('Delete button clicked for product ID:', productId);

                if (!productId) {
                    console.error('Product ID not found on remove button.');
                    showToast('Could not remove item: Product ID missing.', 'error');
                    return;
                }
                // Show confirmation modal before removing
                showConfirmationModal(
                    'Are you sure you want to remove this item from your cart?',
                    function () {
                        // --- Confirmation Callback for Remove Button ---
                        console.log('*** Confirm Callback Entered for product ID:', productId, '***');
                        removeCartItem(productId); // Call the dedicated remove function
                    }
                );
            }

            // --- Clear Cart Button ---
            if (event.target.matches('#clear-cart-btn')) {
                event.preventDefault();
                console.log('Clear Cart button clicked.');
                // Define the confirmation callback for clearing the cart
                const confirmCallback = () => {
                    console.log('*** Clear Cart Confirm Callback Entered ***');
                    console.log('Making POST request to clear cart...');
                    // Disable buttons during operation
                    const allButtons = document.querySelectorAll('button');
                    allButtons.forEach(btn => { btn.disabled = true; });

                    const csrfToken = getCsrfToken();

                    // Send request to the clear cart API endpoint
                    fetch('/api/cart/clear', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ csrf_token: csrfToken })
                    })
                        .then(response => {
                            console.log('Received response from clear cart request. Status:', response.status);
                            const contentType = response.headers.get("content-type");
                            // Handle response validation
                            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                                return response.json();
                            } else if (!response.ok) {
                                if (response.status === 403) {
                                    showToast("Your session may have expired or the request was tampered with. Please refresh the page and try again.", "error");
                                    allButtons.forEach(btn => { btn.disabled = false; });
                                    return Promise.reject(new Error('CSRF validation failed (403)'));
                                }
                                return response.text().then(text => {
                                    throw new Error(`Clear cart failed: ${response.status} ${response.statusText}. ${text}`);
                                });
                            } else {
                                return response.text().then(text => {
                                    throw new Error("Expected JSON response from clear cart, got non-JSON: " + text);
                                });
                            }
                        })
                        .then(data => {
                            console.log('Parsed clear cart response data:', data);
                            if (data.success) {
                                console.log('Attempting to update UI after clearing cart...');
                                // Update the entire cart UI to show the empty state
                                updateCartUI({
                                    is_empty: true,
                                    total_items: 0,
                                    total_price: 0
                                });
                                showToast('Cart cleared.', 'success');
                                updateCartIcon(false); // Set icon to empty
                                updateCartBadge(); // Update badge (will hide it)
                                // Buttons remain disabled as the cart page content is replaced
                            } else {
                                showToast('Error: ' + (data.message || 'Could not clear cart.'), 'error');
                                // Re-enable buttons if clearing failed
                                allButtons.forEach(btn => { btn.disabled = false; });
                            }
                        })
                        .catch(error => {
                            if (error.message !== 'CSRF validation failed (403)') {
                                console.error('Error during clear cart fetch operation:', error);
                                showToast(`An error occurred: ${error.message}. Please try again.`, 'error');
                            }
                            // Re-enable buttons on error (even if it was a CSRF error, as the user might want to try again after refresh)
                            allButtons.forEach(btn => { btn.disabled = false; });
                        });
                }; // End of confirmCallback definition
                // Show the confirmation modal
                showConfirmationModal('Are you sure you want to clear your entire cart?', confirmCallback);
            } // End of clear-cart-btn match
        }); // End of cartContainer click listener
    } // End of if (cartContainer)

    /**
     * Updates the quantity of a specific item in the cart via an API call.
     * Disables quantity controls during the request.
     * @param {string|number} productId - The ID of the product to update.
     * @param {number} newQuantity - The new quantity for the product.
     */
    function updateCartItemQuantity(productId, newQuantity) {
        if (!productId) {
            console.error('Product ID not found for quantity update.');
            return;
        }

        // Disable quantity controls to prevent concurrent updates
        const quantityControlsContainer = document.querySelector(`.cart-item[data-product-id="${productId}"] .quantity-controls`);
        let quantityButtons = [];
        if (quantityControlsContainer) {
            quantityButtons = quantityControlsContainer.querySelectorAll('.quantity-btn, .quantity-input');
            quantityButtons.forEach(el => { el.disabled = true; });
        }

        const csrfToken = getCsrfToken();

        // Prepare data for API
        const data = {
            product_id: productId,
            quantity: newQuantity,
            csrf_token: csrfToken
        };

        // Send request to update cart API endpoint
        fetch('/api/cart/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                // Handle response validation
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else if (!response.ok) {
                    if (response.status === 403) {
                        showToast("Your session may have expired or the request was tampered with. Please refresh the page and try again.", "error");
                        if (quantityControlsContainer) { // Re-enable specific controls
                            quantityButtons.forEach(el => { el.disabled = false; });
                        }
                        return Promise.reject(new Error('CSRF validation failed (403)'));
                    }
                    return response.text().then(text => {
                        throw new Error(`Update cart failed: ${response.status} ${response.statusText}. ${text}`);
                    });
                } else {
                    return response.text().then(text => {
                        throw new Error("Expected JSON response from update cart, got non-JSON: " + text);
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    // Update the cart UI based on the response data
                    updateCartUI(data); // This will re-enable controls if successful
                    // Show appropriate toast message
                    if (data.updated_product) {
                        if (data.updated_product.new_quantity <= 0) {
                            // This case should ideally be handled by removeCartItem, but included for robustness
                            showToast('Item removed from cart.', 'success');
                        } else {
                            showToast('Cart updated successfully.', 'success');
                        }
                    }
                } else {
                    showToast('Error: ' + (data.message || 'Could not update cart.'), 'error');
                    // Re-enable controls if update failed
                    if (quantityControlsContainer) {
                        quantityButtons.forEach(el => { el.disabled = false; });
                    }
                }
            })
            .catch(error => {
                if (error.message !== 'CSRF validation failed (403)') {
                    console.error('Update Cart Fetch error:', error);
                    showToast(`An error occurred: ${error.message}. Please try again.`, 'error');
                }
                // Re-enable controls on fetch error
                if (quantityControlsContainer) {
                    quantityButtons.forEach(el => { el.disabled = false; });
                }
            });
    }

    /**
     * Updates the entire cart page UI based on data received from API calls (update, remove, clear).
     * Handles displaying the empty cart message or updating item details and totals.
     * Re-enables quantity controls after successful updates and re-initializes them if needed.
     * @param {object} data - The data object received from the cart API. Expected properties:
     *                        `total_items` (number), `is_empty` (boolean), `total_price` (number),
     *                        `updated_product` (object, optional - contains `product_id`, `new_quantity`, `new_total`).
     */
    function updateCartUI(data) {
        // Update header icon and badge based on total items
        updateCartIcon(data.total_items > 0);
        updateCartBadge(); // Fetches count internally

        const currentCartContainer = document.querySelector('.cart-container'); // Re-fetch in case it was replaced

        // If the cart is empty, replace the container content with the empty message
        if (data.is_empty) {
            if (currentCartContainer) {
                currentCartContainer.innerHTML = `
                    <h1>Your Shopping Cart</h1>
                    <div class="empty-cart">
                        <img src="/assets/images/cart/empty_shopping_cart.png" alt="Empty Shopping Cart" class="empty-cart-image">
                        <p>Your shopping cart is empty.</p>
                        <a href="/categories" class="continue-shopping-btn">Continue Shopping</a>
                    </div>
                `;
            }
            return; // Stop further UI updates
        }

        // If an item was specifically updated (not a full clear/load)
        const updatedProduct = data.updated_product;
        if (updatedProduct && currentCartContainer) {
            const productId = updatedProduct.product_id;
            const itemRow = currentCartContainer.querySelector(`.cart-item[data-product-id="${productId}"]`);

            // If the new quantity is zero or less, remove the row
            if (updatedProduct.new_quantity <= 0 && itemRow) {
                itemRow.remove();
            }
            // Otherwise, update the quantity input and item total price
            else if (itemRow) {
                const quantityInput = itemRow.querySelector('.quantity-input');
                const totalElement = itemRow.querySelector('.product-total');
                if (quantityInput) {
                    quantityInput.value = updatedProduct.new_quantity;
                    // Update the data attribute used for change detection
                    quantityInput.dataset.previousValue = updatedProduct.new_quantity.toString();
                }
                if (totalElement && updatedProduct.new_total !== undefined) {
                    totalElement.textContent = `$${parseFloat(updatedProduct.new_total).toFixed(2)}`;
                }
            }
        }

        // Update the overall cart total price
        if (currentCartContainer) {
            const cartTotalElement = currentCartContainer.querySelector('#cart-total-price');
            if (cartTotalElement && data.total_price !== undefined) {
                cartTotalElement.textContent = `$${parseFloat(data.total_price).toFixed(2)}`;
            }
        }

        // Re-enable all quantity controls after a successful update
        // and re-initialize them if they were not part of a full page replacement
        if (currentCartContainer) {
            const allQuantityControls = currentCartContainer.querySelectorAll('.quantity-controls');
            allQuantityControls.forEach(qc => {
                const qInput = qc.querySelector('.quantity-input');
                const qDec = qc.querySelector('.decrease-btn');
                const qInc = qc.querySelector('.increase-btn');
                if (qInput) qInput.disabled = false;
                if (qDec) qDec.disabled = false;
                if (qInc) qInc.disabled = false;
            });

            // If cart items were re-rendered by PHP and reloaded via AJAX,
            // or if this function is called after a full cart load,
            // it might be necessary to re-run setupCartQuantityControls.
            // For now, assume individual updates don't require full re-init,
            // but a full cart load (e.g. after clear then add) would.
            // The initial setupCartQuantityControls handles the load.
            // This re-enabling is for after an AJAX update.
        }
    }

    /**
     * Updates the cart icon image in the header (filled or empty).
     * @param {boolean} filled - True if the cart has items, false otherwise.
     */
    function updateCartIcon(filled) {
        const cartImage = document.querySelector('.cart-image');
        if (cartImage) {
            if (filled) {
                // Set to filled cart icon
                cartImage.src = '/assets/images/cart/filled_shopping_cart.png';
                cartImage.alt = 'Shopping Cart';
            } else {
                // Set to empty cart icon
                cartImage.src = '/assets/images/cart/empty_shopping_cart.png';
                cartImage.alt = 'Empty Shopping Cart';
            }
        }
    }

    /**
     * Removes a specific item from the cart via an API call.
     * Disables all buttons during the request.
     * @param {string|number} productId - The ID of the product to remove.
     */
    function removeCartItem(productId) {
        if (!productId) {
            console.error('Product ID not found for removal.');
            return;
        }

        // Disable all buttons on the page during the operation
        const allButtons = document.querySelectorAll('button');
        allButtons.forEach(btn => { btn.disabled = true; });

        const csrfToken = getCsrfToken();
        const deleteUrl = `/api/cart/item/${productId}`; // This is for the removeItem method in controller
        // If using the /api/cart/remove endpoint, the body would be { product_id: productId, csrf_token: csrfToken }
        // For /api/cart/item/{product_id}, the product_id is in the URL, body just needs csrf_token

        console.log('Making POST request to:', deleteUrl);

        // Send request to remove item API endpoint (using POST as per previous logic)
        fetch(deleteUrl, { // Using the removeItem route
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ csrf_token: csrfToken }) // Body for removeItem
        })
            .then(response => {
                console.log('Received response from DELETE request. Status:', response.status);
                // Handle response validation
                const contentType = response.headers.get("content-type");
                if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else if (!response.ok) {
                    if (response.status === 403) {
                        showToast("Your session may have expired or the request was tampered with. Please refresh the page and try again.", "error");
                        allButtons.forEach(btn => { btn.disabled = false; });
                        return Promise.reject(new Error('CSRF validation failed (403)'));
                    }
                    return response.text().then(text => {
                        throw new Error(`Remove item failed: ${response.status} ${response.statusText}. ${text}`);
                    });
                } else {
                    return response.text().then(text => {
                        throw new Error("Expected JSON response from remove item, got non-JSON: " + text);
                    });
                }
            })
            .then(data => {
                console.log('Parsed response data:', data);
                if (data.success) {
                    console.log('Attempting to update UI after deletion...');
                    // Remove the item row from the DOM
                    const currentCartContainer = document.querySelector('.cart-container');
                    if (currentCartContainer) {
                        const itemRow = currentCartContainer.querySelector(`.cart-item[data-product-id="${productId}"]`);
                        if (itemRow) itemRow.remove();
                    }

                    // Update the total price display
                    if (currentCartContainer) {
                        const cartTotalElement = currentCartContainer.querySelector('#cart-total-price');
                        if (cartTotalElement && data.total_price !== undefined) {
                            const totalPrice = parseFloat(data.total_price);
                            cartTotalElement.textContent = !isNaN(totalPrice) ? `$${totalPrice.toFixed(2)}` : '$--.--';
                        }
                    }

                    // If the cart is now empty, update the entire UI
                    if (data.is_empty) {
                        updateCartUI({
                            is_empty: true,
                            total_items: 0,
                            total_price: 0
                        });
                    } else {
                        // Otherwise, just update icon and badge, and re-enable controls
                        updateCartIcon(true); // Cart is not empty
                        updateCartBadge();
                        // Re-enable relevant controls after successful removal of one item
                        if (currentCartContainer) {
                            const allQuantityControls = currentCartContainer.querySelectorAll('.quantity-controls .quantity-btn, .quantity-controls .quantity-input');
                            allQuantityControls.forEach(el => el.disabled = false);
                        }
                    }
                    showToast('Item removed from cart.', 'success');

                } else {
                    showToast('Error: ' + (data.message || 'Could not remove item from cart.'), 'error');
                }
                // Re-enable all buttons after operation completes (success or error),
                // unless cart became empty (UI replaced)
                if (!data.is_empty) {
                    allButtons.forEach(btn => { btn.disabled = false; });
                }
            })
            .catch(error => {
                if (error.message !== 'CSRF validation failed (403)') {
                    console.error('Error details during delete fetch operation:', error);
                    console.error('Remove Cart Item Fetch error:', error);
                    showToast(`An error occurred: ${error.message}. Please try again.`, 'error');
                }
                // Re-enable buttons on fetch error
                allButtons.forEach(btn => { btn.disabled = false; });
            });
    }


    // --- Cart Badge Update ---
    const cartCountBadge = document.getElementById('cart-count-badge');

    /**
     * Fetches the current number of items in the cart from the API.
     * @returns {Promise<number>} A promise that resolves with the cart item count, or 0 on error.
     */
    async function fetchCartCount() {
        // If badge element doesn't exist, no need to fetch
        if (!cartCountBadge) {
            return 0;
        }
        try {
            // Fetch count from API
            const response = await fetch('/api/cart/count', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            // Handle HTTP errors
            if (!response.ok) {
                const errorText = await response.text().catch(() => 'Could not read error response body');
                throw new Error(`HTTP error! status: ${response.status}. ${errorText}`);
            }
            // Check content type and parse JSON
            const contentType = response.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                const data = await response.json();
                // Validate the count received
                if (data && typeof data.count === 'number' && data.count >= 0) {
                    return data.count;
                } else {
                    console.error('Invalid cart count data format or negative count received:', data);
                    return 0; // Return 0 for invalid data
                }
            } else {
                // Handle non-JSON responses
                const text = await response.text();
                throw new Error("Expected JSON response for cart count, got non-JSON: " + text);
            }
        } catch (error) {
            // Handle fetch errors
            console.error('Error fetching cart count:', error);
            return 0; // Return 0 on error
        }
    }

    /**
     * Updates the cart item count badge in the header.
     * Fetches the count using `fetchCartCount` and updates the badge visibility and text content.
     */
    async function updateCartBadge() {
        if (!cartCountBadge) {
            return; // Do nothing if badge element doesn't exist
        }
        const count = await fetchCartCount(); // Get the current count
        if (count > 0) {
            // If count is positive, show badge and set text
            cartCountBadge.textContent = count;
            cartCountBadge.classList.add('visible');
        } else {
            // If count is 0 or less, hide badge and clear text
            cartCountBadge.textContent = '';
            cartCountBadge.classList.remove('visible');
        }
    }

    // Initial update of the cart badge on page load
    updateCartBadge();

    // --- Product Detail Page Quantity Controls ---
    const productDetailForms = document.querySelectorAll('.add-to-cart-form-detail');

    productDetailForms.forEach(form => {
        const controlsContainer = form.querySelector('.quantity-controls');
        const quantityInput = form.querySelector('.quantity-input'); // For getting min/max

        if (!controlsContainer || !quantityInput) {
            console.warn('Could not find quantity controls or input for product detail form:', form);
            return;
        }

        const productId = form.dataset.productId;
        if (!productId) {
            console.warn('Product ID not found on product detail form:', form);
            return;
        }

        const minQuantity = parseInt(quantityInput.min, 10) || 1;
        const maxQuantity = parseInt(quantityInput.max, 10) || 99; // Stock quantity

        initializeQuantityControls(controlsContainer, {
            productId: productId,
            minQuantity: minQuantity,
            maxQuantity: maxQuantity
            // No onQuantityChange or onQuantityZero needed for product detail page,
            // as it only updates the input locally before "Add to Cart" is clicked.
        });
    });


    // --- Initial Page Load Logic for Categories Page ---
    const productsWrapper = document.querySelector('.products-wrapper');
    if (mainCategorySelect && productsWrapper) { // Ensure we are on the categories page
        const activeMainCatId = productsWrapper.dataset.activeMainCategoryId;
        const activeSubCatId = productsWrapper.dataset.activeSubCategoryId;

        console.log("Initial page load on categories page.");
        console.log("Active Main Category ID from PHP:", activeMainCatId);
        console.log("Active Sub Category ID from PHP:", activeSubCatId);

        // If a main category is pre-selected (either by URL filter or default)
        if (mainCategorySelect.value && mainCategorySelect.value !== 'all') {
            const initialMainCategoryId = mainCategorySelect.value; // This is the one selected in the dropdown
            console.log(`Initial main category dropdown value: ${initialMainCategoryId}. Fetching sub-categories...`);

            // Fetch subcategories. If activeSubCatId is present, pass it to select it.
            fetchAndPopulateSubcategories(initialMainCategoryId, activeSubCatId)
                .then(() => {
                    console.log("Subcategories populated (or attempted).");
                    // If activeSubCatId was provided and successfully selected by fetchAndPopulateSubcategories,
                    // it should have already triggered a 'change' event to load products.
                    // If there was no activeSubCatId, or it wasn't found, products for the main category
                    // should have been loaded by the PHP. If the main category dropdown itself was changed
                    // by the user, its event listener handles product loading.
                    // This initial load handles the case where PHP sets the main category, and potentially a sub-category.
                })
                .catch(error => {
                    console.error("Error during initial subcategory population:", error);
                });
        } else {
            console.log("No initial main category selected in dropdown, or 'all' selected. PHP should have loaded initial products.");
        }
    }

    // --- Order Cancellation Modal (My Orders Page) ---
    const cancelOrderModal = document.getElementById('cancelOrderModal');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const modalCloseBtn = document.getElementById('modalCloseBtn'); // Assumes a close button with this ID
    const cancelOrderForm = document.getElementById('cancelOrderForm'); // The form inside the modal
    let previouslyFocusedElement = null; // To restore focus after closing modal

    // Check if all necessary modal elements exist
    if (cancelOrderModal && confirmCancelBtn && modalCloseBtn && cancelOrderForm) {

        // Event listener for buttons that trigger the cancel modal
        document.addEventListener('click', function (event) {
            // Check if the clicked element is a cancel button using data attributes
            if (event.target.matches('[data-bs-toggle="modal"][data-bs-target="#cancelOrderModal"]')) {
                const cancelUrl = event.target.getAttribute('data-cancel-url'); // Get the specific cancel URL
                if (cancelUrl) {
                    cancelOrderForm.action = cancelUrl; // Set the form's action dynamically
                }
                previouslyFocusedElement = event.target; // Store the button that was clicked
                openModal(); // Open the modal
            }
        });

        // Event listener for the confirmation button inside the modal
        confirmCancelBtn.addEventListener('click', function () {
            cancelOrderForm.submit(); // Submit the form to perform cancellation
        });

        // Event listener for the modal's close button
        modalCloseBtn.addEventListener('click', function () {
            closeModal();
        });

        // Event listener to close modal if backdrop is clicked
        cancelOrderModal.addEventListener('click', function (event) {
            if (event.target === cancelOrderModal) { // Clicked on the modal background itself
                closeModal();
            }
        });

        // Event listener to close modal with the Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && cancelOrderModal.classList.contains('modal-visible')) {
                closeModal();
            }
        });

        // --- Accessibility: Trap focus within the modal ---
        cancelOrderModal.addEventListener('keydown', function (event) {
            if (event.key === 'Tab' && cancelOrderModal.classList.contains('modal-visible')) {
                // Find all focusable elements within the modal
                const focusableElements = cancelOrderModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                // If Shift+Tab is pressed on the first element, wrap focus to the last
                if (event.shiftKey && document.activeElement === firstElement) {
                    event.preventDefault();
                    lastElement.focus();
                }
                // If Tab is pressed on the last element, wrap focus to the first
                else if (!event.shiftKey && document.activeElement === lastElement) {
                    event.preventDefault();
                    firstElement.focus();
                }
            }
        });

        /**
         * Opens the order cancellation modal and handles accessibility attributes.
         */
        function openModal() {
            cancelOrderModal.inert = false; // Make modal content interactive
            cancelOrderModal.removeAttribute('aria-hidden');
            cancelOrderModal.classList.add('modal-visible');
            cancelOrderModal.setAttribute('aria-modal', 'true');
            cancelOrderModal.setAttribute('role', 'dialog');
            // Set focus to the close button after a short delay
            setTimeout(() => {
                modalCloseBtn.focus();
            }, 50);
        }

        /**
         * Closes the order cancellation modal, restores focus, and handles accessibility attributes.
         */
        function closeModal() {
            // Restore focus to the element that opened the modal, or body if unavailable
            const elementToFocus = previouslyFocusedElement || document.body;
            try {
                elementToFocus.focus();
            } catch (e) {
                console.error("Error focusing element:", e);
                document.body.focus(); // Fallback to body
            }
            cancelOrderModal.inert = true; // Make modal content non-interactive
            cancelOrderModal.classList.remove('modal-visible');
            cancelOrderModal.removeAttribute('aria-modal');
            cancelOrderModal.setAttribute('aria-hidden', 'true'); // Hide from screen readers
            previouslyFocusedElement = null; // Clear stored element
        }
    } else {
        // Log warnings if any modal elements are missing
        // if (!cancelOrderModal) console.warn("Cancel order modal (#cancelOrderModal) not found.");
        // if (!confirmCancelBtn) console.warn("Confirm cancel button (#confirmCancelBtn) not found.");
        // if (!modalCloseBtn) console.warn("Modal close button (#modalCloseBtn) not found.");
        // if (!cancelOrderForm) console.warn("Cancel order form (#cancelOrderForm) not found.");
    }

}); // End DOMContentLoaded
