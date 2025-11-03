<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Helper\JsonResponse;
use Infinri\Core\Helper\Logger;

/**
 * Upload Single Image for Image Picker
 */
class Upload
{
    private const CSRF_TOKEN_ID = CsrfTokenIds::UPLOAD;

    public function __construct(private readonly CsrfGuard $csrfGuard) {}

    public function execute(Request $request): Response
    {
        try {
            // Validate CSRF token for file uploads
            if (!$request->isPost() || !$this->csrfGuard->validateToken(self::CSRF_TOKEN_ID, $request->getParam('_csrf_token'))) {
                return JsonResponse::csrfError();
            }

            $targetPath = PathHelper::getMediaPath() . '/wysiwyg';

            // Create target directory if it doesn't exist
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }

            if (!is_writable($targetPath)) {
                throw new \RuntimeException('Target directory is not writable');
            }

            if (!isset($_FILES['image'])) {
                throw new \RuntimeException('No file uploaded');
            }

            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException($this->getUploadErrorMessage($file['error']));
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                throw new \RuntimeException('Failed to initialize file info');
            }
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes, true)) {
                throw new \RuntimeException('Invalid file type: ' . $mimeType);
            }

            // Validate file size (5MB max)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new \RuntimeException('File too large (max 5MB)');
            }

            // Sanitize filename to prevent path traversal
            $originalName = basename($file['name']); // Remove any path components
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            // Whitelist allowed extensions
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new \RuntimeException('Invalid file extension: ' . $extension);
            }

            // Generate unique filename with sanitized extension
            $filename = uniqid('img_', true) . '.' . $extension;
            $targetFile = $targetPath . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                chmod($targetFile, 0644);

                // Return relative URL
                $url = '/media/wysiwyg/' . $filename;

                return JsonResponse::success([
                    'url' => $url,
                    'filename' => $filename
                ]);
            } else {
                throw new \RuntimeException('Failed to move uploaded file');
            }

        } catch (\Throwable $e) {
            Logger::error('Upload exception', ['error' => $e->getMessage()]);
            return JsonResponse::error($e->getMessage());
        }
    }

    private function getUploadErrorMessage(int $error): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
        ];

        return $errors[$error] ?? "Unknown upload error ($error)";
    }
}
