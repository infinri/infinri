<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfGuard;

class Picker
{
    private string $mediaPath;
    private string $baseUrl = '/media';

    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';

        if (!is_dir($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }
    }

    public function execute(Request $request): Response
    {
        $response = new Response();

        $currentFolder = $request->getParam('folder', '');
        $currentPath = $this->mediaPath . ($currentFolder ? '/' . $currentFolder : '');

        if (strpos(realpath($currentPath), realpath($this->mediaPath)) !== 0) {
            $currentPath = $this->mediaPath;
            $currentFolder = '';
        }

        $folders = $this->getFolders($currentPath);
        $images = $this->getImages($currentPath);

        $html = $this->renderPicker($currentFolder, $folders, $images);

        return $response->setBody($html);
    }

    private function getFolders(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $folders = [];
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $folders[] = [
                    'name' => $item,
                    'count' => count(glob($fullPath . '/*')),
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

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $relativePath = str_replace($this->mediaPath, '', $fullPath);
            $images[] = [
                'name' => $item,
                'url' => $this->baseUrl . $relativePath,
                'modified' => filemtime($fullPath),
            ];
        }

        usort($images, static fn(array $a, array $b) => $b['modified'] <=> $a['modified']);

        return $images;
    }

    private function renderPicker(string $currentFolder, array $folders, array $images): string
    {
        $breadcrumbs = $this->generateBreadcrumbs($currentFolder);
        $foldersHtml = $this->renderFolders($folders, $currentFolder);
        $imagesHtml = $this->renderImages($images);

        $uploadTokenValue = $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD);
        $uploadTokenField = sprintf(
            '<input type="hidden" name="_csrf_token" value="%s" />',
            htmlspecialchars($uploadTokenValue, ENT_QUOTES, 'UTF-8')
        );
        $csrfTokensJson = json_encode([
            'upload' => $uploadTokenValue,
        ], JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Select Image</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f9fafb; padding: 20px; }
        .header { margin-bottom: 20px; }
        .breadcrumbs { display: flex; gap: 8px; color: #666; font-size: 14px; margin-bottom: 15px; }
        .breadcrumbs a { color: #2563eb; text-decoration: none; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #059669; color: white; display: none; }
        .folders { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 20px; }
        .folder { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb; cursor: pointer; text-align: center; transition: all 0.2s; }
        .folder:hover { border-color: #2563eb; }
        .folder-icon { font-size: 36px; margin-bottom: 8px; }
        .images { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .image-card { background: white; border-radius: 6px; overflow: hidden; border: 2px solid #e5e7eb; cursor: pointer; transition: all 0.2s; }
        .image-card:hover { border-color: #94a3b8; }
        .image-card.selected { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .image-preview { width: 100%; height: 180px; object-fit: cover; background: #f9fafb; }
        .image-name { padding: 10px; font-size: 13px; text-align: center; word-break: break-all; }
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-content h2 { margin-bottom: 20px; }
        .modal-content input[type="file"] { width: 100%; margin-bottom: 15px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body>
    <div class="header">
        <div class="breadcrumbs">{$breadcrumbs}</div>
        <div class="toolbar">
            <button class="btn btn-primary" onclick="showUploadModal()">Upload</button>
            <button id="select-btn" class="btn btn-success" onclick="selectImage()">Select Image</button>
        </div>
    </div>
    
    {$foldersHtml}
    {$imagesHtml}
    
    <!-- Upload Modal -->
    <div id="upload-modal" class="modal">
        <div class="modal-content">
            <h2>Upload Images</h2>
            <form id="upload-form" enctype="multipart/form-data">
                <input type="hidden" name="folder" value="{$currentFolder}">
                {$uploadTokenField}
                <input type="file" id="file-input" name="files[]" multiple accept="image/*" required>
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="hideUploadModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const csrfTokens = {$csrfTokensJson};
        let selectedImageUrl = null;

        function showUploadModal() {
            document.getElementById('upload-modal').classList.add('show');
        }

        function hideUploadModal() {
            document.getElementById('upload-modal').classList.remove('show');
        }

        function selectImageCard(url, element) {
            document.querySelectorAll('.image-card').forEach(card => card.classList.remove('selected'));
            element.classList.add('selected');
            selectedImageUrl = url;
            document.getElementById('select-btn').style.display = 'inline-block';
        }

        function selectImage() {
            if (!selectedImageUrl) {
                return;
            }

            window.parent.postMessage({
                type: 'imageSelected',
                url: selectedImageUrl
            }, '*');
        }

        document.getElementById('upload-form').onsubmit = async (event) => {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.set('_csrf_token', csrfTokens.upload);

            try {
                const response = await fetch('/admin/infinri_media/media/uploadmultiple', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.uploaded.length > 0) {
                    const folder = formData.get('folder');
                    const firstImage = result.uploaded[0];
                    const imageUrl = '/media' + (folder ? '/' + folder : '') + '/' + firstImage;

                    window.parent.postMessage({
                        type: 'imageSelected',
                        url: imageUrl
                    }, '*');
                } else {
                    alert('Upload failed: ' + (result.error ?? 'Unknown error'));
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            }
        };
    </script>
</body>
</html>
HTML;
    }

    private function generateBreadcrumbs(string $currentFolder): string
    {
        $html = '<a href="/admin/infinri_media/media/picker">Home</a>';

        if ($currentFolder) {
            $parts = explode('/', $currentFolder);
            $path = '';

            foreach ($parts as $part) {
                $path .= ($path ? '/' : '') . $part;
                $html .= ' <span>›</span> <a href="/admin/infinri_media/media/picker?folder=' . urlencode($path) . '">' . htmlspecialchars($part) . '</a>';
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
                '<div class="folder" onclick="location.href=\'/admin/infinri_media/media/picker?folder=%s\'">
                    <div class="folder-icon">📁</div>
                    <div class="folder-name">%s</div>
                </div>',
                urlencode($folderPath),
                htmlspecialchars($folder['name'])
            );
        }

        $html .= '</div>';

        return $html;
    }

    private function renderImages(array $images): string
    {
        if (empty($images)) {
            return '<div class="empty-state">
                <div style="font-size: 48px; margin-bottom: 10px;">🖼️</div>
                <p>No images here. Upload some!</p>
            </div>';
        }

        $html = '<div class="images">';

        foreach ($images as $image) {
            $html .= sprintf(
                '<div class="image-card" onclick="selectImageCard(\'%s\', this)">
                    <img src="%s" alt="%s" class="image-preview">
                    <div class="image-name">%s</div>
                </div>',
                htmlspecialchars($image['url']),
                htmlspecialchars($image['url']),
                htmlspecialchars($image['name']),
                htmlspecialchars($image['name'])
            );
        }

        $html .= '</div>';

        return $html;
    }
}
