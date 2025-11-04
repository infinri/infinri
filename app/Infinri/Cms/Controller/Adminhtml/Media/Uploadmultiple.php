<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\JsonResponse;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Security\CsrfGuard;

/**
 * Upload Multiple Images.
 */
class Uploadmultiple
{
    private const CSRF_TOKEN_ID = CsrfTokenIds::UPLOAD;

    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
    }

    public function execute(Request $request): Response
    {
        try {
            if (! $request->isPost() || ! $this->csrfGuard->validateToken(self::CSRF_TOKEN_ID, $request->getParam('_csrf_token'))) {
                return JsonResponse::csrfError();
            }

            error_log('UploadMultiple called');
            error_log('$_FILES: ' . print_r($_FILES, true));
            error_log('$_POST: ' . print_r($_POST, true));

            // Sanitize folder parameter to prevent directory traversal
            $folder = $request->getParam('folder', '');
            if ($folder) {
                // Remove any path traversal attempts
                $folder = str_replace(['..', '\\', '\0'], '', $folder);
                $folder = trim($folder, '/');
                // Whitelist allowed characters: alphanumeric, underscore, hyphen, forward slash
                if (! preg_match('/^[a-zA-Z0-9_\/-]+$/', $folder)) {
                    throw new \RuntimeException('Invalid folder name');
                }
            }
            $mediaPath = PathHelper::getMediaPath();
            $targetPath = $mediaPath . ($folder ? '/' . $folder : '');

            error_log('Target path: ' . $targetPath);
            error_log('Media path: ' . $mediaPath);

            // Create target directory if it doesn't exist
            if (! is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
                error_log('Created directory: ' . $targetPath);
            }

            if (! is_writable($targetPath)) {
                throw new \RuntimeException('Target directory is not writable: ' . $targetPath);
            }

            $uploaded = [];
            $errors = [];

            if (! isset($_FILES['files'])) {
                throw new \RuntimeException('No files uploaded. $_FILES is empty.');
            }

            $files = $_FILES['files'];
            $fileCount = \is_array($files['name']) ? \count($files['name']) : 1;

            error_log('File count: ' . $fileCount);

            for ($i = 0; $i < $fileCount; $i++) {
                $name = \is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmpName = \is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = \is_array($files['error']) ? $files['error'][$i] : $files['error'];

                error_log("Processing file $i: $name, error: $error");

                if (\UPLOAD_ERR_OK !== $error) {
                    $errorMsg = $this->getUploadErrorMessage($error);
                    $errors[] = "$name: $errorMsg";
                    error_log("Upload error for $name: $errorMsg");
                    continue;
                }

                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $finfo = finfo_open(\FILEINFO_MIME_TYPE);
                if (false === $finfo) {
                    $errors[] = "$name: Failed to initialize file info";
                    continue;
                }
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                error_log("File $name mime type: $mimeType");

                if (! \in_array($mimeType, $allowedTypes, true)) {
                    $errors[] = "$name: Invalid file type ($mimeType)";
                    continue;
                }

                // Sanitize filename to prevent path traversal
                $filename = basename($name); // Remove path components
                $extension = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));

                // Whitelist allowed extensions
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                if (! \in_array($extension, $allowedExtensions, true)) {
                    $errors[] = "$name: Invalid file extension ($extension)";
                    continue;
                }

                // Sanitize filename: keep only safe characters
                $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);

                // Add unique prefix to prevent collisions
                $filename = uniqid('', true) . '_' . $filename;
                $targetFile = $targetPath . '/' . $filename;

                error_log("Moving $tmpName to $targetFile");

                if (move_uploaded_file($tmpName, $targetFile)) {
                    chmod($targetFile, 0644);
                    $uploaded[] = $filename;
                    error_log("Successfully uploaded: $filename");
                } else {
                    $errors[] = "$name: Failed to move uploaded file";
                    error_log("Failed to move $name");
                }
            }

            return JsonResponse::success([
                'uploaded' => $uploaded,
                'count' => \count($uploaded),
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            error_log('Upload exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return JsonResponse::error($e->getMessage());
        }
    }

    private function getUploadErrorMessage(int $error): string
    {
        $errors = [
            \UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            \UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            \UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            \UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            \UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            \UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            \UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];

        return $errors[$error] ?? "Unknown upload error ($error)";
    }
}
