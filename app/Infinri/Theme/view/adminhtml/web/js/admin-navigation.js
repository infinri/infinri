/**
 * Admin Navigation
 * Handles menu item clicks and submenu toggle
 */
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initAdminNavigation();
    });
    
    function initAdminNavigation() {
        const menuItems = document.querySelectorAll('.admin-menu-item.has-children');
        
        menuItems.forEach(function(item) {
            const header = item.querySelector('.menu-header');
            const submenu = item.querySelector('.admin-submenu');
            
            if (header && submenu) {
                // Open active submenus by default
                if (item.classList.contains('active')) {
                    item.classList.add('open');
                }
                
                // Add click handler
                header.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSubmenu(item);
                });
            }
        });
        
        // Open active menu items on load
        openActiveMenus();
    }
    
    function toggleSubmenu(menuItem) {
        const isOpen = menuItem.classList.contains('open');
        
        // Close all other submenus at the same level
        const siblings = menuItem.parentElement.querySelectorAll(':scope > .admin-menu-item.has-children');
        siblings.forEach(function(sibling) {
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
        activeItems.forEach(function(item) {
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
})();
