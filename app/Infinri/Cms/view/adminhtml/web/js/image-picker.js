/**
 * Image Picker - Inline Implementation
 * 
 * Extends ImagePickerBase for inline modal usage (embedded in forms)
 * Used in: Form pages with image picker widgets
 */
(function() {
    'use strict';
    
    class InlinePicker extends window.ImagePickerBase {
        constructor(config) {
            super(config);
            this.targetField = null;
        }
        
        /**
         * Override cache elements to include modal-specific elements
         */
        cacheElements() {
            super.cacheElements();
            
            // Additional modal elements
            this.elements.modal = document.getElementById('image-picker-modal');
            this.elements.modalFrame = document.getElementById('image-picker-frame');
            this.elements.modalClose = document.getElementById('btn-close-image-picker');
        }
        
        /**
         * Override to add modal-specific event handlers
         */
        bindEvents() {
            super.bindEvents();
            this.initModalTriggers();
            this.initModalClose();
            this.initMessageReceiver();
        }
        
        /**
         * Initialize buttons that trigger the modal
         */
        initModalTriggers() {
            document.querySelectorAll('.btn-image-picker').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.targetField = btn.dataset.field;
                    this.openModal();
                });
            });
        }
        
        /**
         * Initialize modal close button
         */
        initModalClose() {
            const { modalClose } = this.elements;
            if (modalClose) {
                modalClose.addEventListener('click', () => this.closeModal());
            }
            
            // Close modal when clicking outside
            const { modal } = this.elements;
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.closeModal();
                    }
                });
            }
        }
        
        /**
         * Initialize message receiver for iframe communication
         */
        initMessageReceiver() {
            window.addEventListener('message', (e) => {
                if (e.data.type === 'imageSelected' && e.data.url) {
                    this.insertImageUrl(e.data.url);
                }
            });
        }
        
        /**
         * Open the picker modal
         */
        openModal() {
            const { modal, modalFrame } = this.elements;
            if (modal && modalFrame) {
                modalFrame.src = '/admin/cms/media/picker';
                modal.style.display = 'flex';
            }
        }
        
        /**
         * Close the picker modal
         */
        closeModal() {
            const { modal, modalFrame } = this.elements;
            if (modal && modalFrame) {
                modal.style.display = 'none';
                modalFrame.src = '';
            }
        }
        
        /**
         * Insert image URL into target field
         */
        insertImageUrl(url) {
            if (this.targetField) {
                const field = document.getElementById(this.targetField);
                if (field) {
                    // Insert at cursor position for textareas
                    if (field.tagName === 'TEXTAREA') {
                        const startPos = field.selectionStart;
                        const endPos = field.selectionEnd;
                        const before = field.value.substring(0, startPos);
                        const after = field.value.substring(endPos);
                        
                        field.value = before + `<img src="${url}" alt="Image">` + after;
                        field.selectionStart = field.selectionEnd = startPos + url.length + 18;
                        field.focus();
                    } else {
                        field.value = url;
                    }
                }
            }
            this.closeModal();
        }
        
        /**
         * Override onInsert - not used in inline version
         */
        onInsert() {
            // Handled by iframe postMessage instead
        }
        
        /**
         * Override onCancel - not used in inline version
         */
        onCancel() {
            this.closeModal();
        }
    }
    
    // Initialize when DOM is ready and expose globally
    let pickerInstance = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        pickerInstance = new InlinePicker();
        pickerInstance.init();
        
        // Make closeImagePicker available globally for iframe communication
        window.closeImagePicker = function() {
            if (pickerInstance) {
                pickerInstance.closeModal();
            }
        };
    });
    
})();
