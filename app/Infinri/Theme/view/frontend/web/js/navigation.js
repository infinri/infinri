(function () {
    'use strict';

    const InfinriNavigation = {
        /**
         * Initialize navigation
         */
        init() {
            this.initMobileMenu();
            this.initDropdowns();
        },

        /**
         * Initialize mobile menu toggle
         */
        initMobileMenu() {
            const toggleButton = document.querySelector('.nav-toggle');
            const navMenu = document.querySelector('.nav-menu');

            if (!toggleButton || !navMenu) return;

            toggleButton.addEventListener('click', () => {
                const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';

                toggleButton.setAttribute('aria-expanded', !isExpanded);
                navMenu.classList.toggle('is-open');

                // Prevent body scroll when menu is open
                if (!isExpanded) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });

            // Close menu on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && navMenu.classList.contains('is-open')) {
                    toggleButton.setAttribute('aria-expanded', 'false');
                    navMenu.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
            });

            // Close menu on window resize to desktop
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (window.innerWidth >= 992 && navMenu.classList.contains('is-open')) {
                        toggleButton.setAttribute('aria-expanded', 'false');
                        navMenu.classList.remove('is-open');
                        document.body.style.overflow = '';
                    }
                }, 250);
            });
        },

        /**
         * Initialize dropdown menus
         */
        initDropdowns() {
            const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');

            dropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = toggle.getAttribute('data-dropdown-toggle');
                    const dropdown = document.getElementById(targetId);

                    if (dropdown) {
                        const isOpen = dropdown.classList.contains('is-open');

                        // Close all other dropdowns
                        document.querySelectorAll('.dropdown.is-open').forEach(d => {
                            d.classList.remove('is-open');
                        });

                        // Toggle current dropdown
                        if (!isOpen) {
                            dropdown.classList.add('is-open');
                        }
                    }
                });
            });

            // Close dropdowns on outside click
            document.addEventListener('click', (e) => {
                if (!e.target.closest('[data-dropdown-toggle]') && !e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown.is-open').forEach(dropdown => {
                        dropdown.classList.remove('is-open');
                    });
                }
            });
        },

        /**
         * Set active navigation item
         * @param {string} path Current path
         */
        setActiveItem(path) {
            const navLinks = document.querySelectorAll('.nav-link');

            navLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href === path || (path !== '/' && href.startsWith(path))) {
                    link.closest('.nav-item').classList.add('active');
                } else {
                    link.closest('.nav-item').classList.remove('active');
                }
            });
        }
    };

    // Expose to global scope
    window.InfinriNavigation = InfinriNavigation;
})();
