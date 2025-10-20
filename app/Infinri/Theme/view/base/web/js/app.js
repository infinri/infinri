/**
 * Infinri App
 * Main application initialization
 */
(function() {
    'use strict';
    
    const InfinriApp = {
        /**
         * Initialize the application
         */
        init() {
            this.ready();
        },
        
        /**
         * Initialize all modules
         */
        initModules() {
            if (window.InfinriUtils) {
                window.InfinriUtils.init();
            }

            this.waitForModule('InfinriNavigation', (module) => {
                if (typeof module.init === 'function') {
                    module.init();
                }
            });
        },
        
        /**
         * DOM ready callback
         */
        ready() {
            const initialize = () => {
                this.initModules();
                this.removeSplashScreen();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialize, { once: true });
            } else {
                initialize();
            }
        },
        
        /**
         * Remove splash screen or loading indicator
         */
        removeSplashScreen() {
            const splash = document.querySelector('.splash-screen');
            if (splash) {
                splash.classList.add('fade-out');
                setTimeout(() => splash.remove(), 300);
            }
        },

        /**
         * Wait for module to be available on window
         * @param {string} moduleName Global module name
         * @param {Function} callback Callback to run with module
         * @param {number} retries Remaining retries
         */
        waitForModule(moduleName, callback, retries = 10) {
            const module = window[moduleName];

            if (module) {
                callback(module);
                return;
            }

            if (retries <= 0) {
                return;
            }

            setTimeout(() => this.waitForModule(moduleName, callback, retries - 1), 50);
        }
    };
    
    // Expose to global scope
    window.InfinriApp = InfinriApp;
    
    // Initialize
    InfinriApp.init();
})();
