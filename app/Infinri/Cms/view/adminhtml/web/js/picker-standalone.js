/**
 * Image Picker - Standalone Implementation
 *
 * Extends ImagePickerBase for standalone iframe usage
 * Used in: /admin/cms/media/picker
 */
(function () {
    'use strict';

    class StandalonePicker extends window.ImagePickerBase {
        /**
         * Override onInsert to send message to parent window
         */
        onInsert() {
            if (this.selectedImage && window.parent) {
                window.parent.postMessage({
                    type: 'imageSelected',
                    url: this.selectedImage
                }, '*');

                if (window.parent.closeImagePicker) {
                    window.parent.closeImagePicker();
                }
            }
        }

        /**
         * Override onCancel to close picker
         */
        onCancel() {
            if (window.parent && window.parent.closeImagePicker) {
                window.parent.closeImagePicker();
            }
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        const picker = new StandalonePicker();
        picker.init();
    });

})();
