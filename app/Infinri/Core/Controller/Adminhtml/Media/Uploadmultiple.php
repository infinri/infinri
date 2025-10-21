<?php

declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Security\CsrfGuard;

/**
 * Upload Multiple Images
 */
class Uploadmultiple
{
    private string $mediaPath;
    private const CSRF_TOKEN_ID = CsrfTokenIds::UPLOAD;
    
    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        try {
            if (!$request->isPost() || !$this->csrfGuard->validateToken(self::CSRF_TOKEN_ID, $request->getParam('_csrf_token'))) {
                $response->setForbidden();
                return $response->setBody(json_encode([
                    'success' => false,
                    'error' => 'Invalid CSRF token',
                ]));
            }

            error_log('UploadMultiple called');
            error_log('$_FILES: ' . print_r($_FILES, true));
            error_log('$_POST: ' . print_r($_POST, true));
            
            $folder = $request->getParam('folder', '');
            $targetPath = $this->mediaPath . ($folder ? '/' . $folder : '');
            
            error_log('Target path: ' . $targetPath);
            error_log('Media path: ' . $this->mediaPath);
            
            // Create target directory if it doesn't exist
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
                error_log('Created directory: ' . $targetPath);
            }
            
            if (!is_writable($targetPath)) {
                throw new \RuntimeException('Target directory is not writable: ' . $targetPath);
            }
            
            $uploaded = [];
            $errors = [];
            
            if (!isset($_FILES['files'])) {
                throw new \RuntimeException('No files uploaded. $_FILES is empty.');
            }
            
            $files = $_FILES['files'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            
            error_log('File count: ' . $fileCount);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                
                error_log("Processing file $i: $name, error: $error");
                
                if ($error !== UPLOAD_ERR_OK) {
                    $errorMsg = $this->getUploadErrorMessage($error);
                    $errors[] = "$name: $errorMsg";
                    error_log("Upload error for $name: $errorMsg");
                    continue;
                }
                
                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                
                error_log("File $name mime type: $mimeType");
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $errors[] = "$name: Invalid file type ($mimeType)";
                    continue;
                }
                
                $filename = basename($name);
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
            
            $response->setBody(json_encode([
                'success' => count($uploaded) > 0,
                'uploaded' => $uploaded,
                'count' => count($uploaded),
                'errors' => $errors
            ]));

        } catch (\Throwable $e) {
            error_log('Upload exception: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            $response->setServerError();
            $response->setBody(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }

        return $response;
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
