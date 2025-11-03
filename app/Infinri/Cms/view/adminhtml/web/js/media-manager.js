/**
 * Media Manager JavaScript
 * Handles media library interactions (upload, folders, image actions)
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function () {
        // Get CSRF tokens and current folder from data attributes
        const mediaContent = document.querySelector('.media-content');
        const csrfTokens = mediaContent ? JSON.parse(mediaContent.getAttribute('data-csrf-tokens') || '{}') : {};
        const currentFolder = mediaContent ? mediaContent.getAttribute('data-current-folder') || '' : '';

        // Modal show/hide handlers
        const btnUploadModal = document.getElementById('btn-upload-modal');
        const btnFolderModal = document.getElementById('btn-folder-modal');
        const btnCancelUpload = document.getElementById('btn-cancel-upload');
        const btnCancelFolder = document.getElementById('btn-cancel-folder');
        const uploadModal = document.getElementById('upload-modal');
        const folderModal = document.getElementById('folder-modal');

        if (btnUploadModal && uploadModal) {
            btnUploadModal.addEventListener('click', function () {
                uploadModal.classList.add('show');
            });
        }

        if (btnFolderModal && folderModal) {
            btnFolderModal.addEventListener('click', function () {
                folderModal.classList.add('show');
            });
        }

        if (btnCancelUpload && uploadModal) {
            btnCancelUpload.addEventListener('click', function () {
                uploadModal.classList.remove('show');
            });
        }

        if (btnCancelFolder && folderModal) {
            btnCancelFolder.addEventListener('click', function () {
                folderModal.classList.remove('show');
            });
        }

        // Folder navigation
        document.querySelectorAll('.media-folder').forEach(function (folder) {
            folder.addEventListener('click', function () {
                const url = this.getAttribute('data-folder-url');
                if (url) {
                    window.location.href = url;
                }
            });
        });

        // Image actions - Copy URL
        document.querySelectorAll('.btn-copy-url').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const url = this.getAttribute('data-url');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        alert('URL copied: ' + url);
                    }).catch(function (err) {
                        console.error('Failed to copy URL:', err);
                    });
                }
            });
        });

        // Image actions - Delete
        document.querySelectorAll('.btn-delete-image').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const name = this.getAttribute('data-name');
                if (!confirm('Delete ' + name + '?')) {
                    return;
                }

                fetch('/admin/cms/media/delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        file: name,
                        folder: currentFolder,
                        _csrf_token: csrfTokens.delete
                    })
                }).then(function () {
                    window.location.reload();
                }).catch(function (err) {
                    console.error('Delete failed:', err);
                    alert('Failed to delete image');
                });
            });
        });

        // File upload area
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('file-input');

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function () {
                fileInput.click();
            });

            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function () {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');

                const dt = new DataTransfer();
                Array.from(e.dataTransfer.files).forEach(function (file) {
                    dt.items.add(file);
                });
                fileInput.files = dt.files;
            });

            fileInput.addEventListener('change', function () {
                const count = fileInput.files.length;
                if (count > 0) {
                    const uploadText = uploadArea.querySelector('.media-upload-text');
                    if (uploadText) {
                        uploadText.textContent = count + ' file(s) selected';
                    }
                }
            });
        }

        // Upload form submission
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm && fileInput) {
            uploadForm.addEventListener('submit', function (e) {
                e.preventDefault();

                if (fileInput.files.length === 0) {
                    alert('Please select files to upload');
                    return;
                }

                const formData = new FormData();
                const folderInput = uploadForm.querySelector('input[name="folder"]');
                if (folderInput) {
                    formData.append('folder', folderInput.value);
                }
                formData.append('_csrf_token', csrfTokens.upload || '');

                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append('files[]', fileInput.files[i]);
                }

                fetch('/admin/cms/media/uploadmultiple', {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (result) {
                        if (result.success) {
                            window.location.reload();
                        } else {
                            alert('Upload failed: ' + (result.error || 'Unknown error'));
                        }
                    })
                    .catch(function (err) {
                        console.error('Upload error:', err);
                        alert('Upload error: ' + err.message);
                    });
            });
        }

        // Folder form submission
        const folderForm = document.getElementById('folder-form');
        if (folderForm) {
            folderForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(folderForm);
                formData.set('_csrf_token', csrfTokens.createFolder || '');

                fetch('/admin/cms/media/createfolder', {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) {
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Failed to create folder');
                        }
                    })
                    .catch(function (err) {
                        console.error('Folder creation error:', err);
                        alert('Error: ' + err.message);
                    });
            });
        }
    });
})();
