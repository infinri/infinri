/**
 * Infinri Utils
 * Utility functions for common tasks
 */
(function() {
    'use strict';
    
    const InfinriUtils = {
        /**
         * Initialize utilities
         */
        init() {
            // Initialization code if needed
        },
        
        /**
         * Debounce function execution
         * @param {Function} func Function to debounce
         * @param {number} wait Wait time in milliseconds
         * @returns {Function} Debounced function
         */
        debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Throttle function execution
         * @param {Function} func Function to throttle
         * @param {number} limit Time limit in milliseconds
         * @returns {Function} Throttled function
         */
        throttle(func, limit = 300) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        /**
         * Check if element is in viewport
         * @param {Element} element DOM element
         * @returns {boolean} True if in viewport
         */
        isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },
        
        /**
         * Get query parameter from URL
         * @param {string} param Parameter name
         * @returns {string|null} Parameter value or null
         */
        getQueryParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        },
        
        /**
         * Set cookie
         * @param {string} name Cookie name
         * @param {string} value Cookie value
         * @param {number} days Days until expiration
         */
        setCookie(name, value, days = 365) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = "expires=" + date.toUTCString();
            document.cookie = name + "=" + value + ";" + expires + ";path=/";
        },
        
        /**
         * Get cookie
         * @param {string} name Cookie name
         * @returns {string|null} Cookie value or null
         */
        getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        },
        
        /**
         * Delete cookie
         * @param {string} name Cookie name
         */
        deleteCookie(name) {
            document.cookie = name + '=; Max-Age=-99999999;';
        },
        
        /**
         * Scroll to element smoothly
         * @param {Element|string} element Element or selector
         * @param {number} offset Offset from top
         */
        scrollTo(element, offset = 0) {
            const el = typeof element === 'string' ? document.querySelector(element) : element;
            if (el) {
                const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        },
        
        /**
         * Format number with thousand separators
         * @param {number} num Number to format
         * @returns {string} Formatted number
         */
        formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
        
        /**
         * Escape HTML special characters
         * @param {string} text Text to escape
         * @returns {string} Escaped text
         */
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    // Expose to global scope
    window.InfinriUtils = InfinriUtils;
})();
