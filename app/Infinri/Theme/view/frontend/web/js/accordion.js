/**
 * Infinri Accordion
 * Accordion expand/collapse functionality
 */
(function() {
    'use strict';
    
    const InfinriAccordion = {
        init() {
            const accordions = document.querySelectorAll('[data-accordion]');
            accordions.forEach(accordion => this.initAccordion(accordion));
        },
        
        initAccordion(accordion) {
            const triggers = accordion.querySelectorAll('[data-accordion-trigger]');
            
            triggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    this.toggle(trigger, accordion);
                });
                
                trigger.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggle(trigger, accordion);
                    }
                });
            });
        },
        
        toggle(trigger, accordion) {
            const item = trigger.closest('[data-accordion-item]');
            const content = item.querySelector('[data-accordion-content]');
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            const allowMultiple = accordion.hasAttribute('data-accordion-multiple');
            
            if (!allowMultiple) {
                const allTriggers = accordion.querySelectorAll('[data-accordion-trigger]');
                allTriggers.forEach(t => {
                    if (t !== trigger) {
                        t.setAttribute('aria-expanded', 'false');
                        const c = t.closest('[data-accordion-item]').querySelector('[data-accordion-content]');
                        c.hidden = true;
                    }
                });
            }
            
            trigger.setAttribute('aria-expanded', !isExpanded);
            content.hidden = isExpanded;
            
            trigger.dispatchEvent(new CustomEvent('accordion:toggle', {
                bubbles: true,
                detail: { expanded: !isExpanded }
            }));
        }
    };
    
    window.InfinriAccordion = InfinriAccordion;
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriAccordion.init());
    } else {
        InfinriAccordion.init();
    }
})();
