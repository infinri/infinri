/**
 * Image Picker Base Module
 * 
 * Shared logic for all image picker implementations
 * Extend this for specific use cases (standalone, inline, etc.)
 */
(function(window) {
    'use strict';
    
    class ImagePickerBase {
        constructor(config) {
            this.config = {
                uploadUrl: '/admin/cms/media/upload',
                galleryUrl: '/admin/cms/media/gallery',
                maxFileSize: 5 * 1024 * 1024, // 5MB
                allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
                ...config
            };
            
            this.isUploading = false;
            this.selectedImage = null;
            this.elements = {};
        }
        
        /**
         * Initialize the picker
         * Override this in subclasses to customize initialization
         */
        init() {
            this.cacheElements();
            this.bindEvents();
            this.loadGallery();
        }
        
        /**
         * Cache DOM elements
         * Override to add subclass-specific elements
         */
        cacheElements() {
            this.elements = {
                uploadArea: document.getElementById('upload-area'),
                fileInput: document.getElementById('file-input'),
                uploadPreview: document.getElementById('upload-preview'),
                insertBtn: document.getElementById('insert-btn'),
                gallery: document.getElementById('image-gallery'),
                imageUrl: document.getElementById('image-url'),
                urlPreview: document.getElementById('url-preview')
            };
        }
        
        /**
         * Bind event handlers
         */
        bindEvents() {
            this.initUpload();
            this.initUrl();
            this.initTabs();
            this.initButtons();
        }
        
        /**
         * Initialize upload functionality
         */
        initUpload() {
            const { uploadArea, fileInput } = this.elements;
            
            if (!uploadArea || !fileInput) return;
            
            // Click to browse
            uploadArea.addEventListener('click', () => {
                if (!this.isUploading) {
                    fileInput.click();
                }
            });
            
            // Drag and drop
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const file = e.dataTransfer.files[0];
                if (file && this.validateFileType(file)) {
                    this.handleFileUpload(file);
                }
            });
            
            // File selected
            fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file && !this.isUploading) {
                    this.handleFileUpload(file);
                }
            });
        }
        
        /**
         * Initialize URL input functionality
         */
        initUrl() {
            const { imageUrl, urlPreview, insertBtn } = this.elements;
            
            if (!imageUrl) return;
            
            imageUrl.addEventListener('input', () => {
                const url = imageUrl.value.trim();
                
                if (url && this.isValidUrl(url)) {
                    this.previewUrl(url);
                    this.selectedImage = url;
                    if (insertBtn) insertBtn.disabled = false;
                } else {
                    if (urlPreview) urlPreview.innerHTML = '';
                    this.selectedImage = null;
                    if (insertBtn) insertBtn.disabled = true;
                }
            });
        }
        
        /**
         * Initialize tab switching
         */
        initTabs() {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabName = tab.dataset.tab;
                    
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    tab.classList.add('active');
                    document.getElementById(tabName + '-tab').classList.add('active');
                });
            });
        }
        
        /**
         * Initialize buttons (insert, cancel)
         * Override in subclasses for custom button behavior
         */
        initButtons() {
            const { insertBtn } = this.elements;
            const cancelBtn = document.getElementById('cancel-btn');
            
            if (insertBtn) {
                insertBtn.addEventListener('click', () => this.onInsert());
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => this.onCancel());
            }
        }
        
        /**
         * Handle file upload
         */
        handleFileUpload(file) {
            if (!this.validateFile(file)) return;
            
            this.isUploading = true;
            this.showPreview(file);
            
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_csrf_token', this.getCsrfToken());
            
            fetch(this.config.uploadUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => this.handleUploadSuccess(data))
            .catch(error => this.handleUploadError(error))
            .finally(() => {
                this.isUploading = false;
            });
        }
        
        /**
         * Handle successful upload
         */
        handleUploadSuccess(data) {
            const { uploadPreview, insertBtn } = this.elements;
            
            if (data.success) {
                this.selectedImage = data.url;
                if (uploadPreview) {
                    uploadPreview.innerHTML = `<img src="${data.url}"><p class="upload-status success">✓ Uploaded successfully</p>`;
                }
                if (insertBtn) {
                    insertBtn.disabled = false;
                }
            } else {
                if (uploadPreview) {
                    uploadPreview.innerHTML = `<p class="upload-status error">Upload failed: ${data.error || 'Unknown error'}</p>`;
                }
                this.selectedImage = null;
            }
        }
        
        /**
         * Handle upload error
         */
        handleUploadError(error) {
            const { uploadPreview } = this.elements;
            
            if (uploadPreview) {
                uploadPreview.innerHTML = `<p class="upload-status error">Upload failed: ${error.message}</p>`;
            }
            this.selectedImage = null;
        }
        
        /**
         * Show file preview while uploading
         */
        showPreview(file) {
            const { uploadPreview } = this.elements;
            if (!uploadPreview) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                uploadPreview.innerHTML = `<img src="${e.target.result}"><p class="upload-status uploading">Uploading...</p>`;
            };
            reader.readAsDataURL(file);
        }
        
        /**
         * Preview URL
         */
        previewUrl(url) {
            const { urlPreview } = this.elements;
            if (!urlPreview) return;
            
            urlPreview.innerHTML = `<img src="${url}" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22>Invalid URL</text></svg>'">`;
        }
        
        /**
         * Load gallery images
         */
        loadGallery() {
            const { gallery, insertBtn } = this.elements;
            if (!gallery) return;
            
            gallery.innerHTML = '<p class="gallery-loading">Loading images...</p>';
            
            fetch(this.config.galleryUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.images.length > 0) {
                        this.renderGallery(data.images);
                    } else {
                        gallery.innerHTML = '<p class="gallery-loading">No images found. Upload some images first!</p>';
                    }
                })
                .catch(error => {
                    gallery.innerHTML = `<p class="gallery-loading">Failed to load images: ${error.message}</p>`;
                });
        }
        
        /**
         * Render gallery images
         */
        renderGallery(images) {
            const { gallery, insertBtn } = this.elements;
            
            // Sort by modified date, newest first
            images.sort((a, b) => b.modified - a.modified);
            
            gallery.innerHTML = '';
            
            images.forEach(image => {
                const item = document.createElement('div');
                item.className = 'image-item';
                item.dataset.url = image.url;
                
                const img = document.createElement('img');
                img.src = image.url;
                img.alt = image.name;
                img.title = image.name;
                
                const checkmark = document.createElement('div');
                checkmark.className = 'checkmark';
                checkmark.textContent = '✓';
                
                item.appendChild(img);
                item.appendChild(checkmark);
                
                item.addEventListener('click', () => {
                    document.querySelectorAll('.image-item').forEach(i => i.classList.remove('selected'));
                    item.classList.add('selected');
                    this.selectedImage = item.dataset.url;
                    if (insertBtn) insertBtn.disabled = false;
                });
                
                gallery.appendChild(item);
            });
        }
        
        /**
         * Validate file type
         */
        validateFileType(file) {
            return file.type.startsWith('image/');
        }
        
        /**
         * Validate file (type and size)
         */
        validateFile(file) {
            if (!this.config.allowedTypes.includes(file.type)) {
                alert(`Invalid file type: ${file.type}`);
                return false;
            }
            
            if (file.size > this.config.maxFileSize) {
                alert(`File too large (max ${this.config.maxFileSize / 1024 / 1024}MB)`);
                return false;
            }
            
            return true;
        }
        
        /**
         * Check if URL is valid
         */
        isValidUrl(url) {
            return url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/');
        }
        
        /**
         * Get CSRF token
         */
        getCsrfToken() {
            return document.body.getAttribute('data-csrf-token') || '';
        }
        
        /**
         * Handle insert button click
         * Override in subclasses for custom behavior
         */
        onInsert() {
            console.warn('onInsert() not implemented');
        }
        
        /**
         * Handle cancel button click
         * Override in subclasses for custom behavior
         */
        onCancel() {
            console.warn('onCancel() not implemented');
        }
    }
    
    // Export to global scope
    window.ImagePickerBase = ImagePickerBase;
    
})(window);
