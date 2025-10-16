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
            this.initModules();
        },
        
        /**
         * Initialize all modules
         */
        initModules() {
            if (window.InfinriUtils) {
                window.InfinriUtils.init();
            }
            
            if (window.InfinriNavigation) {
                window.InfinriNavigation.init();
            }
        },
        
        /**
         * DOM ready callback
         */
        ready() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.onReady();
                });
            } else {
                this.onReady();
            }
        },
        
        /**
         * Callback when DOM is ready
         */
        onReady() {
            console.log('Infinri App Ready');
            this.removeSplashScreen();
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
        }
    };
    
    // Expose to global scope
    window.InfinriApp = InfinriApp;
    
    // Initialize
    InfinriApp.init();
})();
