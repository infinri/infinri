/**
 * Infinri Messages
 * Flash message functionality
 */
(function() {
    'use strict';
    
    const InfinriMessages = {
        /**
         * Initialize messages
         */
        init() {
            this.initCloseButtons();
            this.autoHideMessages();
        },
        
        /**
         * Initialize close buttons
         */
        initCloseButtons() {
            const closeButtons = document.querySelectorAll('[data-dismiss="message"]');
            
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const message = button.closest('.message');
                    if (message) {
                        this.hideMessage(message);
                    }
                });
            });
        },
        
        /**
         * Auto-hide messages after timeout
         * @param {number} timeout Timeout in milliseconds
         */
        autoHideMessages(timeout = 5000) {
            const messages = document.querySelectorAll('.message:not(.message-error)');
            
            messages.forEach(message => {
                setTimeout(() => {
                    this.hideMessage(message);
                }, timeout);
            });
        },
        
        /**
         * Hide a message with animation
         * @param {Element} message Message element
         */
        hideMessage(message) {
            message.style.opacity = '0';
            message.style.transform = 'translateY(-10px)';
            message.style.transition = 'opacity 0.3s, transform 0.3s';
            
            setTimeout(() => {
                message.remove();
            }, 300);
        },
        
        /**
         * Show a new message
         * @param {string} text Message text
         * @param {string} type Message type (success, error, warning, info)
         */
        showMessage(text, type = 'info') {
            const messagesContainer = document.querySelector('.messages') || this.createMessagesContainer();
            
            const message = document.createElement('div');
            message.className = `message message-${type}`;
            message.setAttribute('role', 'alert');
            message.setAttribute('aria-live', 'polite');
            
            message.innerHTML = `
                <div class="message-content">
                    <span class="message-icon"></span>
                    <span class="message-text">${this.escapeHtml(text)}</span>
                </div>
                <button type="button" class="message-close" aria-label="Close message" data-dismiss="message">
                    ×
                </button>
            `;
            
            messagesContainer.appendChild(message);
            
            // Initialize close button for new message
            const closeButton = message.querySelector('[data-dismiss="message"]');
            closeButton.addEventListener('click', () => this.hideMessage(message));
            
            // Auto-hide after timeout
            if (type !== 'error') {
                setTimeout(() => this.hideMessage(message), 5000);
            }
        },
        
        /**
         * Create messages container if it doesn't exist
         * @returns {Element} Messages container
         */
        createMessagesContainer() {
            const container = document.createElement('div');
            container.className = 'messages';
            
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.insertBefore(container, mainContent.firstChild);
            } else {
                document.body.insertBefore(container, document.body.firstChild);
            }
            
            return container;
        },
        
        /**
         * Escape HTML
         * @param {string} text Text to escape
         * @returns {string} Escaped text
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // Expose to global scope
    window.InfinriMessages = InfinriMessages;
    
    // Auto-initialize if messages exist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriMessages.init());
    } else {
        InfinriMessages.init();
    }
})();
