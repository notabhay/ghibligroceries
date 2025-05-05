/**
 * @file admin-layout.js
 * @description Handles the collapsible sidebar functionality for the admin panel
 *              on mobile/smaller screen views. Toggles a CSS class on the sidebar
 *              and main content area when the toggle button is clicked.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Select the toggle button and sidebar elements
    const toggleButton = document.getElementById('sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    const adminContainer = document.querySelector('.admin-container');

    // Check if the elements exist before adding event listeners
    if (toggleButton && adminSidebar && adminContainer) {
        // Check localStorage for saved sidebar state
        const sidebarState = localStorage.getItem('sidebarCollapsed');

        // If there's a saved state and it's 'true', collapse the sidebar on page load
        if (sidebarState === 'true') {
            adminContainer.classList.add('sidebar-collapsed');
        }

        // Add click event listener to the toggle button
        toggleButton.addEventListener('click', function () {
            // Toggle the 'sidebar-collapsed' class on the admin container
            adminContainer.classList.toggle('sidebar-collapsed');

            // Save the current state to localStorage
            const isCollapsed = adminContainer.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }
});