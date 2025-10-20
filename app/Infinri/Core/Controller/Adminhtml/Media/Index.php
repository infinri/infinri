<?php

declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Media Manager - Main Gallery View
 * 
 * Simple, intuitive media manager for uploading and organizing images
 */
class Index
{
    private string $mediaPath;
    private string $baseUrl = '/media';
    
    public function __construct()
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';
        
        // Create media directory if it doesn't exist
        if (!is_dir($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        
        // Get current folder from query parameter (default: root)
        $currentFolder = $request->getParam('folder', '');
        $currentPath = $this->mediaPath . ($currentFolder ? '/' . $currentFolder : '');
        
        // Security: prevent directory traversal
        if (strpos(realpath($currentPath), realpath($this->mediaPath)) !== 0) {
            $currentPath = $this->mediaPath;
            $currentFolder = '';
        }
        
        // Get folders and images
        $folders = $this->getFolders($currentPath);
        $images = $this->getImages($currentPath);
        
        $html = $this->renderMediaManager($currentFolder, $folders, $images);
        
        return $response->setBody($html);
    }
    
    private function getFolders(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }
        
        $folders = [];
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $folders[] = [
                    'name' => $item,
                    'path' => $fullPath,
                    'count' => count(glob($fullPath . '/*'))
                ];
            }
        }
        
        return $folders;
    }
    
    private function getImages(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }
        
        $images = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $path . '/' . $item;
            if (is_file($fullPath)) {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    $relativePath = str_replace($this->mediaPath, '', $fullPath);
                    $images[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'url' => $this->baseUrl . $relativePath,
                        'size' => filesize($fullPath),
                        'modified' => filemtime($fullPath)
                    ];
                }
            }
        }
        
        // Sort by modified date (newest first)
        usort($images, fn($a, $b) => $b['modified'] - $a['modified']);
        
        return $images;
    }
    
    private function renderMediaManager(string $currentFolder, array $folders, array $images): string
    {
        $breadcrumbs = $this->generateBreadcrumbs($currentFolder);
        $foldersHtml = $this->renderFolders($folders, $currentFolder);
        $imagesHtml = $this->renderImages($images);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Media Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f5f5f5; }
        .header { background: #fff; padding: 20px 30px; border-bottom: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .header h1 { font-size: 24px; color: #333; margin-bottom: 10px; }
        .breadcrumbs { display: flex; gap: 8px; color: #666; font-size: 14px; align-items: center; }
        .breadcrumbs a { color: #2563eb; text-decoration: none; }
        .breadcrumbs a:hover { text-decoration: underline; }
        .toolbar { background: #fff; padding: 15px 30px; border-bottom: 1px solid #ddd; display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }
        .content { padding: 30px; max-width: 1400px; margin: 0 auto; }
        .folders { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .folder { background: #fff; padding: 20px; border-radius: 8px; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.2s; text-align: center; }
        .folder:hover { border-color: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .folder-icon { font-size: 48px; margin-bottom: 10px; }
        .folder-name { font-weight: 600; color: #333; margin-bottom: 4px; }
        .folder-count { font-size: 12px; color: #666; }
        .images { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .image-card { background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; transition: all 0.2s; }
        .image-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); transform: translateY(-2px); }
        .image-preview { width: 100%; height: 200px; object-fit: cover; background: #f9fafb; }
        .image-info { padding: 12px; }
        .image-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; word-break: break-all; }
        .image-actions { display: flex; gap: 8px; }
        .image-actions button { flex: 1; padding: 6px; border: 1px solid #ddd; background: #fff; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .image-actions button:hover { background: #f9fafb; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state-icon { font-size: 64px; margin-bottom: 16px; opacity: 0.3; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-content h2 { margin-bottom: 20px; }
        .modal-content input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        #upload-area { border: 2px dashed #ddd; border-radius: 8px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.2s; }
        #upload-area:hover { border-color: #2563eb; background: #f0f9ff; }
        #upload-area.dragover { border-color: #2563eb; background: #dbeafe; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📁 Media Manager</h1>
        <div class="breadcrumbs">
            {$breadcrumbs}
        </div>
    </div>
    
    <div class="toolbar">
        <button class="btn btn-primary" onclick="showUploadModal()">📤 Upload Images</button>
        <button class="btn btn-secondary" onclick="showNewFolderModal()">📁 New Folder</button>
    </div>
    
    <div class="content">
        {$foldersHtml}
        {$imagesHtml}
    </div>
    
    <!-- Upload Modal -->
    <div id="upload-modal" class="modal">
        <div class="modal-content">
            <h2>Upload Images</h2>
            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" name="folder" value="{$currentFolder}">
                <div id="upload-area">
                    <p style="margin-bottom: 10px;">📤 Drag & drop images here</p>
                    <p style="color: #666; font-size: 14px;">or click to browse</p>
                    <input type="file" id="file-input" name="files[]" multiple accept="image/*" style="display: none;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New Folder Modal -->
    <div id="folder-modal" class="modal">
        <div class="modal-content">
            <h2>Create New Folder</h2>
            <form id="folder-form">
                <input type="hidden" name="parent" value="{$currentFolder}">
                <input type="text" name="name" placeholder="Folder name" required>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideFolderModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Define functions immediately in global scope
        function showUploadModal() { 
            document.getElementById('upload-modal').classList.add('show'); 
        }
        function hideUploadModal() { 
            document.getElementById('upload-modal').classList.remove('show'); 
        }
        function showNewFolderModal() { 
            document.getElementById('folder-modal').classList.add('show'); 
        }
        function hideFolderModal() { 
            document.getElementById('folder-modal').classList.remove('show'); 
        }
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('URL copied: ' + url);
            });
        }
        function deleteImage(name) {
            if (!confirm('Delete ' + name + '?')) return;
            
            fetch('/admin/infinri_media/media/delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file: name, folder: '{$currentFolder}' })
            }).then(() => location.reload());
        }
        
        // Wait for DOM to be ready before setting up event handlers
        document.addEventListener('DOMContentLoaded', function() {
        // Upload area drag & drop
        const uploadArea = document.getElementById('upload-area');
        const fileInput = document.getElementById('file-input');
        
        uploadArea.onclick = () => fileInput.click();
        
        uploadArea.ondragover = (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        };
        
        uploadArea.ondragleave = () => uploadArea.classList.remove('dragover');
        
        uploadArea.ondrop = (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const dt = new DataTransfer();
            Array.from(e.dataTransfer.files).forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        };
        
        // Show selected file count
        fileInput.onchange = () => {
            const count = fileInput.files.length;
            if (count > 0) {
                uploadArea.querySelector('p').textContent = count + ' file(s) selected';
            }
        };
        
        // Upload form
        document.getElementById('upload-form').onsubmit = async (e) => {
            e.preventDefault();
            
            const fileInput = document.getElementById('file-input');
            console.log('Files selected:', fileInput.files.length);
            
            if (fileInput.files.length === 0) {
                alert('Please select files to upload');
                return;
            }
            
            const formData = new FormData();
            formData.append('folder', document.querySelector('input[name="folder"]').value);
            
            // Add all files
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('files[]', fileInput.files[i]);
                console.log('Adding file:', fileInput.files[i].name);
            }
            
            try {
                const response = await fetch('/admin/infinri_media/media/uploadmultiple', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    alert('Server error. Check console for details.');
                    return;
                }
                
                console.log('Upload result:', result);
                
                if (result.success) {
                    if (result.errors && result.errors.length > 0) {
                        alert('Partial upload:\\nUploaded: ' + result.count + '\\nErrors:\\n' + result.errors.join('\\n'));
                    }
                    location.reload();
                } else {
                    alert('Upload failed: ' + (result.error || 'Unknown error'));
                }
            } catch (err) {
                console.error('Upload error:', err);
                alert('Upload error: ' + err.message);
            }
        };
        
        // Folder form
        document.getElementById('folder-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('/admin/infinri_media/media/createfolder', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Failed to create folder');
                }
            } catch (err) {
                alert('Error: ' + err.message);
            }
        };
        }); // End DOMContentLoaded
    </script>
</body>
</html>
HTML;
    }
    
    private function generateBreadcrumbs(string $currentFolder): string
    {
        $html = '<a href="/admin/infinri_media/media/index">Home</a>';
        
        if ($currentFolder) {
            $parts = explode('/', $currentFolder);
            $path = '';
            
            foreach ($parts as $part) {
                $path .= ($path ? '/' : '') . $part;
                $html .= ' <span>›</span> <a href="/admin/infinri_media/media/index?folder=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>';
            }
        }
        
        return $html;
    }
    
    private function renderFolders(array $folders, string $currentFolder): string
    {
        if (empty($folders)) {
            return '';
        }
        
        $html = '<div class="folders">';
        
        foreach ($folders as $folder) {
            $folderPath = $currentFolder ? $currentFolder . '/' . $folder['name'] : $folder['name'];
            $html .= sprintf(
                '<div class="folder" onclick="location.href=\'/admin/infinri_media/media/index?folder=%s\'">
                    <div class="folder-icon">📁</div>
                    <div class="folder-name">%s</div>
                    <div class="folder-count">%d items</div>
                </div>',
                urlencode($folderPath),
                htmlspecialchars($folder['name']),
                $folder['count']
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderImages(array $images): string
    {
        if (empty($images)) {
            return '<div class="empty-state">
                <div class="empty-state-icon">🖼️</div>
                <p>No images in this folder</p>
                <p style="margin-top: 10px; font-size: 14px;">Upload some images to get started</p>
            </div>';
        }
        
        $html = '<div class="images">';
        
        foreach ($images as $image) {
            $html .= sprintf(
                '<div class="image-card">
                    <img src="%s" alt="%s" class="image-preview">
                    <div class="image-info">
                        <div class="image-name">%s</div>
                        <div class="image-actions">
                            <button onclick="copyUrl(\'%s\')">📋 Copy URL</button>
                            <button onclick="deleteImage(\'%s\')">🗑️ Delete</button>
                        </div>
                    </div>
                </div>',
                htmlspecialchars($image['url']),
                htmlspecialchars($image['name']),
                htmlspecialchars($image['name']),
                htmlspecialchars($image['url']),
                htmlspecialchars($image['name'])
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
