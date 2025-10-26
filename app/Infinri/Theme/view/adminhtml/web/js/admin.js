/**
 * Admin JavaScript
 * Core admin functionality
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Infinri Admin Theme Loaded');
        
        // Initialize admin features
        initMobileMenu();
        initTableCheckboxes();
        initAlertClose();
    });
    
    /**
     * Mobile menu toggle
     */
    function initMobileMenu() {
        const menuToggle = document.querySelector('.admin-menu-toggle');
        const navigation = document.querySelector('.admin-navigation');
        
        if (menuToggle && navigation) {
            menuToggle.addEventListener('click', function() {
                navigation.classList.toggle('is-open');
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!navigation.contains(e.target) && !menuToggle.contains(e.target)) {
                    navigation.classList.remove('is-open');
                }
            });
        }
    }
    
    /**
     * Table row selection
     */
    function initTableCheckboxes() {
        // Select all checkbox in grid
        const selectAllCheckbox = document.querySelector('.grid-th-checkbox input[type="checkbox"]');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.grid-td-checkbox input[type="checkbox"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                    updateRowSelection(checkbox);
                });
            });
        }
        
        // Individual row checkboxes
        const rowCheckboxes = document.querySelectorAll('.grid-td-checkbox input[type="checkbox"]');
        rowCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateRowSelection(checkbox);
            });
        });
    }
    
    /**
     * Update row selection state
     */
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (row) {
            if (checkbox.checked) {
                row.classList.add('is-selected');
            } else {
                row.classList.remove('is-selected');
            }
        }
    }
    
    /**
     * Close alert messages
     */
    function initAlertClose() {
        const closeButtons = document.querySelectorAll('.alert-close');
        
        closeButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const alert = button.closest('.admin-alert');
                if (alert) {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }
            });
        });
    }
    
    /**
     * Confirm delete action
     */
    window.confirmDelete = function(message) {
        return confirm(message || 'Are you sure you want to delete this item?');
    };
    
})();
