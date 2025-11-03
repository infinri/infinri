(function () {
    'use strict';

    const InfinriModals = {
        /**
         * Initialize modals
         */
        init() {
            this.initTriggers();
            this.initKeyboardEvents();
        },

        /**
         * Initialize modal triggers
         */
        initTriggers() {
            const triggers = document.querySelectorAll('[data-modal-toggle]');

            triggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = trigger.getAttribute('data-modal-toggle');
                    const modal = document.getElementById(targetId);

                    if (modal) {
                        this.open(modal);
                    }
                });
            });

            // Initialize close buttons
            const closeButtons = document.querySelectorAll('[data-modal-close]');
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const modal = button.closest('.modal');
                    if (modal) {
                        this.close(modal);
                    }
                });
            });

            // Close on backdrop click
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('modal-backdrop')) {
                    const openModal = document.querySelector('.modal.is-open');
                    if (openModal) {
                        this.close(openModal);
                    }
                }
            });
        },

        /**
         * Initialize keyboard events
         */
        initKeyboardEvents() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.is-open');
                    if (openModal) {
                        this.close(openModal);
                    }
                }
            });
        },

        /**
         * Open a modal
         * @param {Element|string} modal Modal element or selector
         */
        open(modal) {
            const modalElement = typeof modal === 'string' ? document.querySelector(modal) : modal;

            if (!modalElement) return;

            // Create or get backdrop
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop';
                document.body.appendChild(backdrop);
            }

            // Open modal
            modalElement.classList.add('is-open');
            backdrop.classList.add('is-open');

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

            // Focus first focusable element
            setTimeout(() => {
                const focusable = modalElement.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable) {
                    focusable.focus();
                }
            }, 100);

            // Trap focus within modal
            this.trapFocus(modalElement);

            // Dispatch event
            modalElement.dispatchEvent(new CustomEvent('modal:open', {bubbles: true}));
        },

        /**
         * Close a modal
         * @param {Element|string} modal Modal element or selector
         */
        close(modal) {
            const modalElement = typeof modal === 'string' ? document.querySelector(modal) : modal;

            if (!modalElement) return;

            // Close modal
            modalElement.classList.remove('is-open');

            // Close backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.classList.remove('is-open');
            }

            // Restore body scroll
            document.body.style.overflow = '';

            // Return focus to trigger
            const trigger = document.querySelector(`[data-modal-toggle="${modalElement.id}"]`);
            if (trigger) {
                trigger.focus();
            }

            // Dispatch event
            modalElement.dispatchEvent(new CustomEvent('modal:close', {bubbles: true}));
        },

        /**
         * Trap focus within modal
         * @param {Element} modal Modal element
         */
        trapFocus(modal) {
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );

            if (focusableElements.length === 0) return;

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            const handleTabKey = (e) => {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            };

            modal.addEventListener('keydown', handleTabKey);
        },

        /**
         * Create a modal programmatically
         * @param {Object} options Modal options
         * @returns {Element} Modal element
         */
        create(options = {}) {
            const {
                id = 'modal-' + Date.now(),
                title = '',
                content = '',
                size = '',
                footer = '',
                closeButton = true
            } = options;

            const modal = document.createElement('div');
            modal.id = id;
            modal.className = 'modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', `${id}-title`);

            const sizeClass = size ? `modal-${size}` : '';

            modal.innerHTML = `
                <div class="modal-dialog ${sizeClass}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title" id="${id}-title">${title}</h3>
                            ${closeButton ? '<button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>' : ''}
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Initialize close button
            const closeBtn = modal.querySelector('[data-modal-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close(modal));
            }

            return modal;
        },

        /**
         * Destroy a modal
         * @param {Element|string} modal Modal element or selector
         */
        destroy(modal) {
            const modalElement = typeof modal === 'string' ? document.querySelector(modal) : modal;

            if (!modalElement) return;

            this.close(modalElement);

            setTimeout(() => {
                modalElement.remove();
            }, 300);
        }
    };

    // Expose to global scope
    window.InfinriModals = InfinriModals;

    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriModals.init());
    } else {
        InfinriModals.init();
    }
})();
