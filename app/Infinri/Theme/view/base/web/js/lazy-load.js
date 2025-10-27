/**
 * Infinri Lazy Load
 * Lazy loading for images and iframes
 */
(function() {
    'use strict';
    
    const InfinriLazyLoad = {
        /**
         * Configuration
         */
        config: {
            selector: '[data-lazy]',
            rootMargin: '50px',
            threshold: 0.01,
            loadingClass: 'lazy-loading',
            loadedClass: 'lazy-loaded',
            errorClass: 'lazy-error'
        },
        
        /**
         * Observer instance
         */
        observer: null,
        
        /**
         * Initialize lazy loading
         * @param {Object} options Configuration options
         */
        init(options = {}) {
            this.config = { ...this.config, ...options };
            
            // Check for Intersection Observer support
            if (!('IntersectionObserver' in window)) {
                console.warn('IntersectionObserver not supported. Loading all images immediately.');
                this.loadAll();
                return;
            }
            
            this.setupObserver();
            this.observe();
        },
        
        /**
         * Setup Intersection Observer
         */
        setupObserver() {
            const options = {
                rootMargin: this.config.rootMargin,
                threshold: this.config.threshold
            };
            
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadElement(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, options);
        },
        
        /**
         * Observe all lazy elements
         */
        observe() {
            const elements = document.querySelectorAll(this.config.selector);
            elements.forEach(element => {
                this.observer.observe(element);
            });
        },
        
        /**
         * Load a single element
         * @param {Element} element Element to load
         */
        loadElement(element) {
            const src = element.getAttribute('data-lazy');
            const srcset = element.getAttribute('data-lazy-srcset');
            
            if (!src) return;
            
            element.classList.add(this.config.loadingClass);
            
            if (element.tagName === 'IMG') {
                this.loadImage(element, src, srcset);
            } else if (element.tagName === 'IFRAME') {
                this.loadIframe(element, src);
            } else {
                this.loadBackground(element, src);
            }
        },
        
        /**
         * Load image
         * @param {Element} img Image element
         * @param {string} src Image source
         * @param {string} srcset Image srcset
         */
        loadImage(img, src, srcset) {
            const tempImg = new Image();
            
            tempImg.onload = () => {
                img.src = src;
                if (srcset) {
                    img.srcset = srcset;
                }
                img.classList.remove(this.config.loadingClass);
                img.classList.add(this.config.loadedClass);
                img.removeAttribute('data-lazy');
                img.removeAttribute('data-lazy-srcset');
                
                // Dispatch load event
                img.dispatchEvent(new CustomEvent('lazy:loaded', { bubbles: true }));
            };
            
            tempImg.onerror = () => {
                img.classList.remove(this.config.loadingClass);
                img.classList.add(this.config.errorClass);
                
                // Dispatch error event
                img.dispatchEvent(new CustomEvent('lazy:error', { bubbles: true }));
            };
            
            tempImg.src = src;
            if (srcset) {
                tempImg.srcset = srcset;
            }
        },
        
        /**
         * Load iframe
         * @param {Element} iframe Iframe element
         * @param {string} src Iframe source
         */
        loadIframe(iframe, src) {
            iframe.src = src;
            
            iframe.onload = () => {
                iframe.classList.remove(this.config.loadingClass);
                iframe.classList.add(this.config.loadedClass);
                iframe.removeAttribute('data-lazy');
                
                // Dispatch load event
                iframe.dispatchEvent(new CustomEvent('lazy:loaded', { bubbles: true }));
            };
            
            iframe.onerror = () => {
                iframe.classList.remove(this.config.loadingClass);
                iframe.classList.add(this.config.errorClass);
                
                // Dispatch error event
                iframe.dispatchEvent(new CustomEvent('lazy:error', { bubbles: true }));
            };
        },
        
        /**
         * Load background image
         * @param {Element} element Element with background
         * @param {string} src Background image source
         */
        loadBackground(element, src) {
            const tempImg = new Image();
            
            tempImg.onload = () => {
                element.style.backgroundImage = `url('${src}')`;
                element.classList.remove(this.config.loadingClass);
                element.classList.add(this.config.loadedClass);
                element.removeAttribute('data-lazy');
                
                // Dispatch load event
                element.dispatchEvent(new CustomEvent('lazy:loaded', { bubbles: true }));
            };
            
            tempImg.onerror = () => {
                element.classList.remove(this.config.loadingClass);
                element.classList.add(this.config.errorClass);
                
                // Dispatch error event
                element.dispatchEvent(new CustomEvent('lazy:error', { bubbles: true }));
            };
            
            tempImg.src = src;
        },
        
        /**
         * Load all images immediately (fallback)
         */
        loadAll() {
            const elements = document.querySelectorAll(this.config.selector);
            elements.forEach(element => {
                this.loadElement(element);
            });
        },
        
        /**
         * Update - observe new elements
         */
        update() {
            if (this.observer) {
                this.observe();
            } else {
                this.loadAll();
            }
        },
        
        /**
         * Destroy observer
         */
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    };
    
    // Expose to global scope
    window.InfinriLazyLoad = InfinriLazyLoad;
    
    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriLazyLoad.init());
    } else {
        InfinriLazyLoad.init();
    }
})();
