/**
 * AI-powered search functionality for the search results page
 * Handles AJAX requests to update results dynamically without page reload
 */
document.addEventListener("DOMContentLoaded", function () {
    console.log("Search Page JS: Script loaded and DOMContentLoaded."); // Debug line
    // Elements
    const searchForm = document.getElementById("ai-search-form");
    const searchInput = document.getElementById("ai-search-input");
    const searchButton = document.getElementById("ai-search-button");
    const resultsContainer = document.getElementById("product-display-area");
    const suggestionArea = document.getElementById("ai-suggestion-area");
    const statusMessage = document.getElementById("search-status-message");

    // Only initialize if we have all the required elements
    if (!searchForm || !searchInput || !searchButton || !resultsContainer) {
        console.warn("Search Page JS: One or more required elements not found.");
        return;
    }

    // Event listener for form submission
    searchForm.addEventListener("submit", function (event) {
        // Only intercept if the search input has content
        if (searchInput.value.trim().length > 0) {
            event.preventDefault(); // Prevent default form submission
            performAiSearch(searchInput.value.trim());

            // Update URL without reloading the page
            const url = new URL(window.location);
            url.searchParams.set("q", searchInput.value.trim());
            window.history.pushState({}, "", url);
        }
    });

    // Handle browser back/forward navigation
    window.addEventListener("popstate", function (event) {
        const url = new URL(window.location);
        const query = url.searchParams.get("q");

        if (query) {
            searchInput.value = query;
            performAiSearch(query);
        }
    });

    /**
     * Performs an AI-powered search using the Gemini API
     * @param {string} query - The search query
     */
    function performAiSearch(query) {
        // Show loading state
        showLoadingState(true);
        updateScreenReaderStatus("Searching for " + query);

        // Make AJAX request to the AI search endpoint
        fetch("/api/ai-search", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ q: query }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                // Hide loading state
                showLoadingState(false);

                // Process and display the results
                if (data.success) {
                    updateSearchResults(data, query);
                    updatePageTitle(query);
                    updateResultCount(data.totalResults, query);

                    // Show corrected term if available
                    if (data.correctedTerm && data.correctedTerm !== query) {
                        showCorrectedTermMessage(data.correctedTerm, query);
                    } else {
                        hideCorrectedTermMessage();
                    }

                    updateScreenReaderStatus(`Showing ${data.totalResults} results for ${query}`);
                } else {
                    showErrorMessage(data.message || "An error occurred during search.");
                    updateScreenReaderStatus("Search error: " + (data.message || "An error occurred during search."));
                }
            })
            .catch((error) => {
                // Hide loading state
                showLoadingState(false);

                console.error("AI Search error:", error);
                showErrorMessage(
                    "Search is temporarily unavailable. Please try again later."
                );
                updateScreenReaderStatus("Search error: Search is temporarily unavailable. Please try again later.");
            });
    }

    /**
     * Shows or hides the loading state
     * @param {boolean} isLoading - Whether to show or hide the loading state
     */
    function showLoadingState(isLoading) {
        if (isLoading) {
            // Change button icon to spinner
            const searchIcon = searchButton.querySelector("i");
            if (searchIcon) {
                searchIcon.className = "fas fa-spinner fa-spin";
            }

            // Disable input and button
            searchInput.disabled = true;
            searchButton.disabled = true;
            searchButton.setAttribute("aria-disabled", "true");

            // Add loading class to form
            searchForm.classList.add("loading");

            // Add loading overlay to results
            const loadingOverlay = document.createElement("div");
            loadingOverlay.className = "loading-overlay";
            loadingOverlay.innerHTML = '<div class="spinner" role="status" aria-label="Loading results"></div>';
            resultsContainer.appendChild(loadingOverlay);
        } else {
            // Restore button icon
            const searchIcon = searchButton.querySelector("i");
            if (searchIcon) {
                searchIcon.className = "fas fa-search";
            }

            // Enable input and button
            searchInput.disabled = false;
            searchButton.disabled = false;
            searchButton.removeAttribute("aria-disabled");

            // Remove loading class from form
            searchForm.classList.remove("loading");

            // Remove loading overlay
            const loadingOverlay = resultsContainer.querySelector(".loading-overlay");
            if (loadingOverlay) {
                loadingOverlay.remove();
            }
        }
    }

    /**
     * Updates the search results in the results container
     * @param {Object} data - The response data from the API
     * @param {string} originalQuery - The original search query
     */
    function updateSearchResults(data, originalQuery) {
        // Clear previous results
        resultsContainer.innerHTML = "";

        // Check if we have products
        if (data.products && data.products.length > 0) {
            // Calculate layout for a 4-column grid
            const totalProducts = data.products.length;
            const rows = Math.ceil(totalProducts / 4);

            // Loop through rows
            for (let i = 0; i < rows; i++) {
                const startIndex = i * 4;
                const endIndex = Math.min(startIndex + 4, totalProducts);

                // Create row container
                const rowDiv = document.createElement("div");
                rowDiv.className = "products-row";

                // Loop through products for this row
                for (let j = startIndex; j < endIndex; j++) {
                    const product = data.products[j];
                    const productCard = createProductCard(product);
                    rowDiv.appendChild(productCard);
                }

                resultsContainer.appendChild(rowDiv);
            }
        } else {
            // No products found
            const noResults = document.createElement("div");
            noResults.className = "no-results";
            noResults.innerHTML = `
                <p>No products found matching '${escapeHTML(
                originalQuery
            )}'. Please try a different search term.</p>
                <a href="/categories" class="browse-categories-btn">Browse Categories</a>
            `;
            resultsContainer.appendChild(noResults);
        }
    }

    /**
     * Creates a product card element
     * @param {Object} product - The product data
     * @returns {HTMLElement} The product card element
     */
    function createProductCard(product) {
        const isLoggedIn = document.body.getAttribute("data-logged-in") === "true";

        // Create article element
        const article = document.createElement("article");
        article.className = "product-card";

        // Create product link
        const productLink = document.createElement("a");
        productLink.href = `/product/${product.product_id}`;
        productLink.className = "product-link";

        // Create product image
        const img = document.createElement("img");
        img.src = product.image_path;
        img.alt = product.name;
        img.className = "product-image";

        // Create product name
        const name = document.createElement("h4");
        name.className = "product-name";
        name.textContent = product.name;

        // Create product price
        const price = document.createElement("p");
        price.className = "product-price";
        price.textContent = `$${parseFloat(product.price).toFixed(2)}`;

        // Append elements to product link
        productLink.appendChild(img);
        productLink.appendChild(name);

        // Add category if available
        if (product.category_name) {
            const category = document.createElement("p");
            category.className = "product-category";
            category.textContent = product.category_name;
            productLink.appendChild(category);
        }

        productLink.appendChild(price);
        article.appendChild(productLink);

        // Add appropriate button based on login status
        if (isLoggedIn) {
            const addButton = document.createElement("button");
            addButton.className = "add-to-cart-btn";
            addButton.setAttribute("data-product-id", product.product_id);
            addButton.textContent = "Add to Cart";

            // Add event listener for cart functionality
            addButton.addEventListener("click", function () {
                addToCart(product.product_id);
            });

            article.appendChild(addButton);
        } else {
            const loginLink = document.createElement("a");
            loginLink.href = "/login";
            loginLink.className = "login-to-purchase-btn";
            loginLink.textContent = "Login to Purchase";
            article.appendChild(loginLink);
        }

        return article;
    }

    /**
     * Updates the page title with the search query
     * @param {string} query - The search query
     */
    function updatePageTitle(query) {
        document.title = `Search Results for "${query}" - GhibliGroceries`;
    }

    /**
     * Updates the result count display
     * @param {number} count - The number of results
     * @param {string} query - The search query
     */
    function updateResultCount(count, query) {
        const pageHeader = document.querySelector(".page-header p");
        if (pageHeader) {
            if (count > 0) {
                pageHeader.textContent = `Found ${count} result${count !== 1 ? "s" : ""
                    } for '${query}'`;
            } else {
                pageHeader.textContent = `No products found matching '${query}'`;
            }
        }
    }

    /**
     * Shows the "Did you mean" suggestion
     * @param {string} correctedTerm - The corrected search term
     * @param {string} originalQuery - The original search query
     */
    function showCorrectedTermMessage(correctedTerm, originalQuery) {
        if (!suggestionArea) return;

        suggestionArea.innerHTML = `
      <div class="ai-suggestion">
        Did you mean: <a href="/search?q=${encodeURIComponent(correctedTerm)}" 
        class="ai-suggestion-link">${escapeHTML(correctedTerm)}</a>?
      </div>
    `;

        // Add click event to the suggestion link
        const suggestionLink = suggestionArea.querySelector(".ai-suggestion-link");
        if (suggestionLink) {
            suggestionLink.addEventListener("click", function (e) {
                e.preventDefault();
                searchInput.value = correctedTerm;
                performAiSearch(correctedTerm);

                // Update URL
                const url = new URL(window.location);
                url.searchParams.set("q", correctedTerm);
                window.history.pushState({}, "", url);
            });
        }
    }

    /**
     * Hides the "Did you mean" suggestion
     */
    function hideCorrectedTermMessage() {
        if (suggestionArea) {
            suggestionArea.innerHTML = "";
        }
    }

    /**
     * Shows an error message in the results container
     * @param {string} message - The error message
     */
    function showErrorMessage(message) {
        resultsContainer.innerHTML = `
      <div class="search-error">
        <p>${escapeHTML(message)}</p>
        <a href="/categories">Browse Categories</a>
      </div>
    `;
    }

    /**
     * Updates the screen reader status message
     * @param {string} message - The status message
     */
    function updateScreenReaderStatus(message) {
        if (statusMessage) {
            statusMessage.textContent = message;
        }
    }

    /**
     * Adds a product to the cart
     * @param {string|number} productId - The ID of the product to add
     */
    function addToCart(productId) {
        const button = document.querySelector(`.add-to-cart-btn[data-product-id="${productId}"]`);
        if (!button) return;

        // Provide visual feedback
        button.textContent = "Adding...";
        button.disabled = true;

        // Send request to add to cart
        fetch("/api/cart/add", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1,
            }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                // Reset button
                button.textContent = "Add to Cart";
                button.disabled = false;

                // Show success message
                if (data.success) {
                    showToast(data.message || "Item added to cart successfully!", "success");

                    // Update cart icon and badge if those functions exist
                    if (typeof updateCartIcon === "function") {
                        updateCartIcon(true);
                    }
                    if (typeof updateCartBadge === "function") {
                        updateCartBadge();
                    }
                } else {
                    showToast("Error: " + (data.message || "Could not add item to cart."), "error");
                }
            })
            .catch((error) => {
                // Reset button
                button.textContent = "Add to Cart";
                button.disabled = false;

                console.error("Add to Cart error:", error);
                showToast("An error occurred. Please try again.", "error");
            });
    }

    /**
     * Escapes HTML special characters to prevent XSS
     * @param {string} str - The string to escape
     * @return {string} The escaped string
     */
    function escapeHTML(str) {
        if (str === null || str === undefined) {
            return "";
        }

        const div = document.createElement("div");
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
        if (typeof window.showToast === "function") {
            window.showToast(message, type);
        } else {
            // Fallback to console
            console.log(`Toast (${type}): ${message}`);
        }
    }
});