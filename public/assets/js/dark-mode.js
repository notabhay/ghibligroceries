/**
 * @file dark-mode.js
 * @description Handles the dark mode toggle functionality for the admin panel.
 *              Toggles a CSS class on the body element when the toggle button is clicked.
 *              Stores the user's preference in localStorage and applies it on page load.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Select the toggle buttons and container elements
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const darkModeToggleHeader = document.getElementById('dark-mode-toggle-header');
    const adminContainer = document.querySelector('.admin-container');

    // Function to toggle dark mode
    function toggleDarkMode() {
        // Toggle the 'dark-mode' class on the admin container
        document.body.classList.toggle('dark-mode');

        // Save the current state to localStorage
        const isDarkMode = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);

        // Update toggle button appearance
        updateToggleAppearance(isDarkMode);
    }

    // Function to update toggle button appearance based on dark mode state
    function updateToggleAppearance(isDarkMode) {
        // Update sidebar toggle
        if (darkModeToggle) {
            const toggleText = darkModeToggle.querySelector('.toggle-text');
            if (toggleText) {
                toggleText.textContent = isDarkMode ? 'Light Mode' : 'Dark Mode';
            }

            // Toggle icon visibility
            const lightIcon = darkModeToggle.querySelector('.light-icon');
            const darkIcon = darkModeToggle.querySelector('.dark-icon');

            if (lightIcon && darkIcon) {
                lightIcon.style.display = isDarkMode ? 'none' : 'inline-block';
                darkIcon.style.display = isDarkMode ? 'inline-block' : 'none';
            }
        }

        // Update header toggle
        if (darkModeToggleHeader) {
            const lightIconHeader = darkModeToggleHeader.querySelector('.light-icon');
            const darkIconHeader = darkModeToggleHeader.querySelector('.dark-icon');

            if (lightIconHeader && darkIconHeader) {
                lightIconHeader.style.display = isDarkMode ? 'none' : 'inline-block';
                darkIconHeader.style.display = isDarkMode ? 'inline-block' : 'none';
            }
        }
    }

    // Check if the elements exist before adding event listeners
    if ((darkModeToggle || darkModeToggleHeader) && adminContainer) {
        // Check localStorage for saved dark mode state
        const darkModeState = localStorage.getItem('darkMode');

        // If there's a saved state and it's 'true', enable dark mode on page load
        if (darkModeState === 'true') {
            document.body.classList.add('dark-mode');
            updateToggleAppearance(true);
        } else {
            // Ensure proper initial state
            updateToggleAppearance(false);
        }

        // Add click event listener to the sidebar toggle button
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', toggleDarkMode);
        }

        // Add click event listener to the header toggle button
        if (darkModeToggleHeader) {
            darkModeToggleHeader.addEventListener('click', toggleDarkMode);
        }
    }
});