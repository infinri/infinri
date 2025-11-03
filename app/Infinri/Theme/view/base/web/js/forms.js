(function () {
    'use strict';

    const InfinriForms = {
        /**
         * Initialize form validation
         */
        init() {
            this.initValidation();
            this.initCharacterCount();
        },

        /**
         * Initialize form validation
         */
        initValidation() {
            const forms = document.querySelectorAll('[data-validate]');

            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                        e.stopPropagation();
                    }

                    form.classList.add('was-validated');
                });

                // Real-time validation
                const inputs = form.querySelectorAll('input, textarea, select');
                inputs.forEach(input => {
                    input.addEventListener('blur', () => {
                        this.validateField(input);
                    });

                    input.addEventListener('input', () => {
                        if (input.classList.contains('is-invalid')) {
                            this.validateField(input);
                        }
                    });
                });
            });
        },

        /**
         * Validate entire form
         * @param {Element} form Form element
         * @returns {boolean} True if valid
         */
        validateForm(form) {
            const inputs = form.querySelectorAll('input, textarea, select');
            let isValid = true;

            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Validate single field
         * @param {Element} field Input element
         * @returns {boolean} True if valid
         */
        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            const required = field.hasAttribute('required');
            const pattern = field.getAttribute('pattern');
            const minLength = field.getAttribute('minlength');
            const maxLength = field.getAttribute('maxlength');
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');

            let isValid = true;
            let errorMessage = '';

            // Required validation
            if (required && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }

            // Email validation
            else if (type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
            }

            // URL validation
            else if (type === 'url' && value) {
                try {
                    new URL(value);
                } catch {
                    isValid = false;
                    errorMessage = 'Please enter a valid URL.';
                }
            }

            // Pattern validation
            else if (pattern && value) {
                const regex = new RegExp(pattern);
                if (!regex.test(value)) {
                    isValid = false;
                    errorMessage = field.getAttribute('data-pattern-message') || 'Please match the requested format.';
                }
            }

            // Min/Max length validation
            else if (minLength && value.length < parseInt(minLength)) {
                isValid = false;
                errorMessage = `Please enter at least ${minLength} characters.`;
            } else if (maxLength && value.length > parseInt(maxLength)) {
                isValid = false;
                errorMessage = `Please enter no more than ${maxLength} characters.`;
            }

            // Number validation
            else if (type === 'number' && value) {
                const num = parseFloat(value);
                if (min && num < parseFloat(min)) {
                    isValid = false;
                    errorMessage = `Value must be at least ${min}.`;
                } else if (max && num > parseFloat(max)) {
                    isValid = false;
                    errorMessage = `Value must be no more than ${max}.`;
                }
            }

            // Update field state
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                this.hideError(field);
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
                this.showError(field, errorMessage);
            }

            return isValid;
        },

        /**
         * Show error message
         * @param {Element} field Input element
         * @param {string} message Error message
         */
        showError(field, message) {
            let feedback = field.parentElement.querySelector('.invalid-feedback');

            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentElement.appendChild(feedback);
            }

            feedback.textContent = message;
            feedback.style.display = 'block';
        },

        /**
         * Hide error message
         * @param {Element} field Input element
         */
        hideError(field) {
            const feedback = field.parentElement.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.style.display = 'none';
            }
        },

        /**
         * Initialize character count
         */
        initCharacterCount() {
            const fields = document.querySelectorAll('[data-character-count]');

            fields.forEach(field => {
                const maxLength = field.getAttribute('maxlength');
                if (!maxLength) return;

                const counter = document.createElement('div');
                counter.className = 'character-count';
                counter.textContent = `0 / ${maxLength}`;
                field.parentElement.appendChild(counter);

                field.addEventListener('input', () => {
                    const length = field.value.length;
                    counter.textContent = `${length} / ${maxLength}`;

                    if (length >= parseInt(maxLength) * 0.9) {
                        counter.style.color = 'var(--warning-color, #ffc107)';
                    } else {
                        counter.style.color = '';
                    }
                });
            });
        },

        /**
         * Clear form validation
         * @param {Element} form Form element
         */
        clearValidation(form) {
            form.classList.remove('was-validated');

            const fields = form.querySelectorAll('.is-valid, .is-invalid');
            fields.forEach(field => {
                field.classList.remove('is-valid', 'is-invalid');
                this.hideError(field);
            });
        },

        /**
         * Serialize form data
         * @param {Element} form Form element
         * @returns {Object} Form data as object
         */
        serialize(form) {
            const formData = new FormData(form);
            const data = {};

            for (const [key, value] of formData.entries()) {
                if (data[key]) {
                    if (!Array.isArray(data[key])) {
                        data[key] = [data[key]];
                    }
                    data[key].push(value);
                } else {
                    data[key] = value;
                }
            }

            return data;
        }
    };

    // Expose to global scope
    window.InfinriForms = InfinriForms;

    // Auto-initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InfinriForms.init());
    } else {
        InfinriForms.init();
    }
})();
