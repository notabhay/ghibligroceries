/**
 * AI-powered search functionality for GhibliGroceries
 * Handles AJAX requests to the Gemini-powered search API and displays results dynamically
 */
document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const searchForm = document.querySelector('.search-bar');
    const searchInput = document.getElementById('ai-search-input');
    const searchButton = document.getElementById('ai-search-button');
    const resultsContainer = document.getElementById('ai-search-results-container');

    // Only initialize if we have all the required elements
    if (!searchForm || !searchInput || !searchButton || !resultsContainer) {
        console.warn('AI Search: One or more required elements not found.');
        return;
    }

    // Event listener for form submission
    searchForm.addEventListener('submit', function (event) {
        // Only intercept if the search input has content
        if (searchInput.value.trim().length > 0) {
            event.preventDefault(); // Prevent default form submission
            performAiSearch(searchInput.value.trim());
        }
        // Otherwise, let the form submit normally to the traditional search
    });

    /**
     * Performs an AI-powered search using the Gemini API
     * @param {string} query - The search query
     */
    function performAiSearch(query) {
        // Show loading state
        showLoadingState(true);

        // Make AJAX request to the AI search endpoint
        fetch('/api/ai-search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ q: query })
        })
            .then(response => {
                // Check if the response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Hide loading state
                showLoadingState(false);

                // Process and display the results
                if (data.success) {
                    displaySearchResults(data, query);
                } else {
                    displayErrorMessage(data.message || 'An error occurred during search.');
                }
            })
            .catch(error => {
                // Hide loading state
                showLoadingState(false);

                console.error('AI Search error:', error);
                displayErrorMessage('Search is temporarily unavailable. Please try again later.');

                // Fallback removed for debugging - display error instead
                // searchForm.submit();
                console.error('AI Search AJAX call failed. Fallback to normal submit prevented for debugging.');
                displayErrorMessage('AI search request failed. Please check console or try again later.');
            });
    }

    /**
     * Shows or hides the loading state
     * @param {boolean} isLoading - Whether to show or hide the loading state
     */
    function showLoadingState(isLoading) {
        if (isLoading) {
            // Change button icon to spinner
            const searchIcon = searchButton.querySelector('i');
            if (searchIcon) {
                searchIcon.className = 'fas fa-spinner fa-spin';
            }

            // Disable input and button
            searchInput.disabled = true;
            searchButton.disabled = true;

            // Add loading class to form
            searchForm.classList.add('loading');
        } else {
            // Restore button icon
            const searchIcon = searchButton.querySelector('i');
            if (searchIcon) {
                searchIcon.className = 'fas fa-search';
            }

            // Enable input and button
            searchInput.disabled = false;
            searchButton.disabled = false;

            // Remove loading class from form
            searchForm.classList.remove('loading');
        }
    }

    /**
     * Displays the search results in the results container
     * @param {Object} data - The response data from the API
     * @param {string} originalQuery - The original search query
     */
    function displaySearchResults(data, originalQuery) {
        // Clear previous results
        resultsContainer.innerHTML = '';

        // Create results header
        const resultsHeader = document.createElement('div');
        resultsHeader.className = 'ai-results-header';

        // Add "Did you mean" suggestion if available
        if (data.correctedTerm && data.correctedTerm !== originalQuery) {
            const suggestion = document.createElement('div');
            suggestion.className = 'ai-suggestion';
            suggestion.innerHTML = `Did you mean: <a href="#" class="ai-suggestion-link">${escapeHTML(data.correctedTerm)}</a>?`;

            // Add click event to suggestion link
            suggestion.querySelector('.ai-suggestion-link').addEventListener('click', function (e) {
                e.preventDefault();
                searchInput.value = data.correctedTerm;
                performAiSearch(data.correctedTerm);
            });

            resultsHeader.appendChild(suggestion);
        }

        resultsContainer.appendChild(resultsHeader);

        // Check if we have products
        if (data.products && data.products.length > 0) {
            // Create product list
            const productList = document.createElement('ul');
            productList.className = 'ai-product-list';

            // Add products to the list (limit to 5 for dropdown)
            const displayLimit = Math.min(data.products.length, 5);
            for (let i = 0; i < displayLimit; i++) {
                const product = data.products[i];
                const productItem = createProductItem(product);
                productList.appendChild(productItem);
            }

            resultsContainer.appendChild(productList);

            // Add "View all results" link if there are more products
            if (data.totalResults > displayLimit) {
                const viewAllLink = document.createElement('div');
                viewAllLink.className = 'ai-view-all';
                viewAllLink.innerHTML = `<a href="/search?q=${encodeURIComponent(originalQuery)}">View all results (${data.totalResults} items)</a>`;
                resultsContainer.appendChild(viewAllLink);
            }
        } else {
            // No products found
            const noResults = document.createElement('div');
            noResults.className = 'ai-no-results';
            noResults.innerHTML = `No products found matching '${escapeHTML(originalQuery)}'. <a href="/categories">Browse categories</a>.`;
            resultsContainer.appendChild(noResults);
        }

        // Show the results container
        resultsContainer.style.display = 'block';

        // Add click event to close results when clicking outside
        document.addEventListener('click', handleOutsideClick);
    }

    /**
     * Creates a product item element for the results list
     * @param {Object} product - The product data
     * @return {HTMLElement} The product item element
     */
    function createProductItem(product) {
        const isLoggedIn = document.body.getAttribute('data-logged-in') === 'true';
        const productItem = document.createElement('li');
        productItem.className = 'ai-product-item';

        // Format price
        const price = parseFloat(product.price);
        const formattedPrice = !isNaN(price) ? price.toFixed(2) : 'N/A';

        // Create product HTML
        productItem.innerHTML = `
            <a href="/product/${product.product_id}">
                <img src="/${escapeHTML(product.image_path)}" alt="${escapeHTML(product.name)}" class="ai-product-image">
                <div class="ai-product-details">
                    <span class="ai-product-name">${escapeHTML(product.name)}</span>
                    ${product.category_name ? `<span class="ai-product-category">${escapeHTML(product.category_name)}</span>` : ''}
                </div>
                <span class="ai-product-price">$${formattedPrice}</span>
            </a>
            ${isLoggedIn
                ? `<button class="add-to-cart-btn" data-product-id="${product.product_id}">Add to Cart</button>`
                : `<a href="/login" class="login-to-purchase-btn">Login to Purchase</a>`
            }
        `;

        // Add event listener for "Add to Cart" button if logged in
        if (isLoggedIn) {
            const addToCartBtn = productItem.querySelector('.add-to-cart-btn');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    addToCart(product.product_id);
                });
            }
        }

        return productItem;
    }

    /**
     * Adds a product to the cart
     * @param {string|number} productId - The ID of the product to add
     */
    function addToCart(productId) {
        const button = document.querySelector(`.add-to-cart-btn[data-product-id="${productId}"]`);
        if (!button) return;

        // Provide visual feedback
        button.textContent = 'Adding...';
        button.disabled = true;

        // Send request to add to cart
        fetch('/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Reset button
                button.textContent = 'Add to Cart';
                button.disabled = false;

                // Show success message
                if (data.success) {
                    showToast(data.message || 'Item added to cart successfully!', 'success');

                    // Update cart icon and badge if those functions exist
                    if (typeof updateCartIcon === 'function') {
                        updateCartIcon(true);
                    }
                    if (typeof updateCartBadge === 'function') {
                        updateCartBadge();
                    }
                } else {
                    showToast('Error: ' + (data.message || 'Could not add item to cart.'), 'error');
                }
            })
            .catch(error => {
                // Reset button
                button.textContent = 'Add to Cart';
                button.disabled = false;

                console.error('Add to Cart error:', error);
                showToast('An error occurred. Please try again.', 'error');
            });
    }

    /**
     * Displays an error message in the results container
     * @param {string} message - The error message to display
     */
    function displayErrorMessage(message) {
        resultsContainer.innerHTML = `
            <div class="ai-error">
                <p>${escapeHTML(message)}</p>
            </div>
        `;
        resultsContainer.style.display = 'block';
    }

    /**
     * Handles clicks outside the search area to close the results
     * @param {Event} event - The click event
     */
    function handleOutsideClick(event) {
        // Check if the click is outside the search form and results container
        if (!searchForm.contains(event.target) && !resultsContainer.contains(event.target)) {
            // Hide results container
            resultsContainer.style.display = 'none';

            // Remove event listener
            document.removeEventListener('click', handleOutsideClick);
        }
    }

    /**
     * Escapes HTML special characters to prevent XSS
     * @param {string} str - The string to escape
     * @return {string} The escaped string
     */
    function escapeHTML(str) {
        if (str === null || str === undefined) {
            return '';
        }

        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    /**
     * Shows a toast notification if the function exists in the global scope
     * @param {string} message - The message to display
     * @param {string} type - The type of toast (success, error, info)
     */
    function showToast(message, type) {
        // Check if the global showToast function exists
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback to alert for testing
            console.log(`Toast (${type}): ${message}`);
        }
    }
});