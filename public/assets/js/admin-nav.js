/**
 * @file admin-nav.js
 * @description Dynamically adds an "Admin Dashboard" link to the navigation menus
 *              if the user is identified as an administrator.
 *              It targets both the main desktop navigation and the mobile menu.
 */

// Wait for the HTML document to be fully loaded and parsed.
document.addEventListener('DOMContentLoaded', function () {
    // Check if the user is an admin by reading the 'data-is-admin' attribute from the body tag.
    const isAdmin = document.body.getAttribute('data-is-admin') === 'true';

    // Proceed only if the user is identified as an admin.
    if (isAdmin) {
        // --- Desktop Navigation ---
        // Find the main desktop navigation list (ul element within header nav).
        const desktopNav = document.querySelector('header nav ul');
        if (desktopNav) {
            // Create a new list item (li) for the admin link.
            const adminLi = document.createElement('li');
            // Create the anchor tag (a) for the admin link.
            const adminLink = document.createElement('a');
            adminLink.href = '/admin/dashboard'; // Set the link destination.
            adminLink.textContent = 'Admin Dashboard'; // Set the visible text of the link.

            // Check if the current page URL path starts with '/admin/dashboard'.
            if (window.location.pathname.startsWith('/admin/dashboard')) {
                // Add the 'active' class to the link if it's the current section.
                adminLink.classList.add('active');
            }

            // Append the admin link (a) to the list item (li).
            adminLi.appendChild(adminLink);

            // Find the existing "Contact" link's list item to insert the admin link before it.
            const contactLi = desktopNav.querySelector('li a[href="/contact"]');
            if (contactLi && contactLi.parentElement) {
                // If the "Contact" link exists, insert the new admin list item before it.
                desktopNav.insertBefore(adminLi, contactLi.parentElement);
            } else {
                // If the "Contact" link is not found, append the admin list item to the end of the navigation.
                desktopNav.appendChild(adminLi);
            }
        }

        // --- Mobile Navigation ---
        // Find the mobile navigation list (ul element within .mobile-menu nav).
        const mobileNav = document.querySelector('.mobile-menu nav ul');
        if (mobileNav) {
            // Create a new list item (li) for the admin link in the mobile menu.
            const adminLi = document.createElement('li');
            // Create the anchor tag (a) for the admin link.
            const adminLink = document.createElement('a');
            adminLink.href = '/admin/dashboard'; // Set the link destination.
            adminLink.textContent = 'Admin Dashboard'; // Set the visible text of the link.

            // Check if the current page URL path starts with '/admin/dashboard'.
            if (window.location.pathname.startsWith('/admin/dashboard')) {
                // Add the 'active' class to the link if it's the current section.
                adminLink.classList.add('active');
            }

            // Append the admin link (a) to the list item (li).
            adminLi.appendChild(adminLink);

            // Find the existing "Contact" link's list item in the mobile menu.
            const contactLi = mobileNav.querySelector('li a[href="/contact"]');
            if (contactLi && contactLi.parentElement) {
                // If the "Contact" link exists, insert the new admin list item before it.
                mobileNav.insertBefore(adminLi, contactLi.parentElement);
            } else {
                // If the "Contact" link is not found, append the admin list item to the end of the mobile navigation.
                mobileNav.appendChild(adminLi);
            }
        }
    }
});
