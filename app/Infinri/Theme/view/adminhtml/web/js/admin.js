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
        initUserMenu();
        initTableCheckboxes();
        initAlertClose();
        initFormImagePicker();
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
     * User menu dropdown
     */
    function initUserMenu() {
        const userTrigger = document.querySelector('.user-trigger');
        const dropdown = document.querySelector('.admin-user-dropdown');
        const userMenu = document.querySelector('.admin-user-menu');
        
        if (userTrigger && dropdown) {
            userTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.hidden = !dropdown.hidden;
            });
        }
        
        // Close dropdown when clicking outside
        if (userMenu && dropdown) {
            document.addEventListener('click', function(event) {
                if (!userMenu.contains(event.target)) {
                    dropdown.hidden = true;
                }
            });
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
     * Form image picker functionality
     */
    function initFormImagePicker() {
        let currentTargetField = null;
        let cursorPosition = 0;
        
        // Track cursor position in textareas
        document.querySelectorAll('textarea').forEach(function(textarea) {
            textarea.addEventListener('click', function() {
                cursorPosition = this.selectionStart;
            });
            textarea.addEventListener('keyup', function() {
                cursorPosition = this.selectionStart;
            });
        });
        
        // Global functions for onclick attributes
        window.openImagePicker = function(fieldName) {
            currentTargetField = document.getElementById(fieldName);
            if (currentTargetField) {
                cursorPosition = currentTargetField.selectionStart;
            }
            
            const modal = document.getElementById('image-picker-modal');
            const iframe = document.getElementById('image-picker-frame');
            if (modal && iframe) {
                iframe.src = '/admin/infinri_media/media/picker';
                modal.style.display = 'flex';
            }
        };
        
        window.closeImagePicker = function() {
            const modal = document.getElementById('image-picker-modal');
            const iframe = document.getElementById('image-picker-frame');
            if (modal) {
                modal.style.display = 'none';
            }
            if (iframe) {
                iframe.src = '';
            }
        };
        
        // Listen for image selection from iframe
        window.addEventListener('message', function(event) {
            if (event.data.type === 'imageSelected' && currentTargetField) {
                const imageUrl = event.data.url;
                const imageTag = '<img src="' + imageUrl + '" alt="Image" style="max-width: 100%;">';
                
                // Insert at cursor position
                const content = currentTargetField.value;
                const newContent = content.substring(0, cursorPosition) + imageTag + content.substring(cursorPosition);
                currentTargetField.value = newContent;
                
                // Move cursor after inserted image
                cursorPosition += imageTag.length;
                currentTargetField.setSelectionRange(cursorPosition, cursorPosition);
                currentTargetField.focus();
                
                if (window.closeImagePicker) {
                    window.closeImagePicker();
                }
            }
        });
    }
    
    /**
     * Confirm delete action
     */
    window.confirmDelete = function(message) {
        return confirm(message || 'Are you sure you want to delete this item?');
    };
    
})();
