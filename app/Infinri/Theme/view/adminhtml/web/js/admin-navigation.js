(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        initAdminNavigation();
    });

    function initAdminNavigation() {
        const menuItems = document.querySelectorAll('.admin-menu-item.has-children');
        console.log('Admin Navigation: Found', menuItems.length, 'menu items with children');

        menuItems.forEach(function (item) {
            const header = item.querySelector('.menu-header');
            const submenu = item.querySelector('.admin-submenu');

            if (header && submenu) {
                // Open active submenus by default
                if (item.classList.contains('active')) {
                    item.classList.add('open');
                }

                // Add click handler
                header.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent click from bubbling to document
                    toggleSubmenu(item);
                });
            }
        });

        // Open active menu items on load
        openActiveMenus();

        // Close submenus when clicking outside
        setupClickOutsideHandler();
    }

    function toggleSubmenu(menuItem) {
        const isOpen = menuItem.classList.contains('open');
        console.log('Toggle submenu:', menuItem, 'Currently open:', isOpen);

        // Close all other submenus at the same level
        const siblings = menuItem.parentElement.querySelectorAll(':scope > .admin-menu-item.has-children');
        siblings.forEach(function (sibling) {
            if (sibling !== menuItem) {
                sibling.classList.remove('open');
            }
        });

        // Toggle current submenu
        if (isOpen) {
            menuItem.classList.remove('open');
        } else {
            menuItem.classList.add('open');
        }
    }

    function openActiveMenus() {
        // Open all parent menus of active items
        const activeItems = document.querySelectorAll('.admin-menu-item.active');
        activeItems.forEach(function (item) {
            // Find parent menu items and open them
            let parent = item.parentElement;
            while (parent) {
                if (parent.classList && parent.classList.contains('admin-menu-item')) {
                    parent.classList.add('open');
                }
                parent = parent.parentElement;
            }
        });
    }

    function closeAllSubmenus() {
        const openMenus = document.querySelectorAll('.admin-menu-item.has-children.open');
        openMenus.forEach(function (menu) {
            menu.classList.remove('open');
        });
    }

    function setupClickOutsideHandler() {
        const sidebar = document.querySelector('.admin-sidebar');

        if (!sidebar) {
            return;
        }

        // Listen for clicks on the entire document
        document.addEventListener('click', function (e) {
            // Check if click is outside the sidebar
            if (!sidebar.contains(e.target)) {
                console.log('Click outside sidebar detected, closing all submenus');
                closeAllSubmenus();
            }
        });

        // Prevent submenu clicks from closing
        const submenus = document.querySelectorAll('.admin-submenu');
        submenus.forEach(function (submenu) {
            submenu.addEventListener('click', function (e) {
                // Allow clicks inside submenu (for navigation links)
                e.stopPropagation();
            });
        });
    }
})();
