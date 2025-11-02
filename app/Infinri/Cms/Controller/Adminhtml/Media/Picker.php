<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Model\Media\MediaLibrary;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;

/**
 * Media Picker Controller
 * 
 * Phase 3.4: SOLID Refactoring - Now uses MediaLibrary service
 */
class Picker
{
    private MediaLibrary $mediaLibrary;

    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
        $mediaPath = dirname(__DIR__, 6) . '/pub/media';
        $this->mediaLibrary = new MediaLibrary($mediaPath, '/media');
    }

    public function execute(Request $request): Response
    {
        $response = new Response();

        // Generate CSRF token for upload
        $csrfToken = $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD);

        // Load the standalone picker template
        $templatePath = dirname(__DIR__, 3) . '/view/adminhtml/templates/media/picker.phtml';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $html = ob_get_clean();
        } else {
            $html = '<p>Error: Picker template not found</p>';
        }

        return $response->setBody($html);
    }

    /**
     * Get folders - delegated to MediaLibrary service
     */
    private function getFolders(string $relativePath): array
    {
        return $this->mediaLibrary->getFolders($relativePath);
    }

    /**
     * Get images - delegated to MediaLibrary service
     * Returns array format for backward compatibility
     */
    private function getImages(string $relativePath): array
    {
        $fileInfoObjects = $this->mediaLibrary->getImages($relativePath);
        
        // Convert FileInfo objects to arrays for template
        return array_map(fn($fileInfo) => [
            'name' => $fileInfo->name,
            'url' => $fileInfo->url,
            'modified' => $fileInfo->modifiedTime,
        ], $fileInfoObjects);
    }
    
    // Phase 5: Dead code removed (renderPicker, generateBreadcrumbs, renderFolders, renderImages)
    // The controller now uses picker.phtml template with existing _image-picker.less styles
}
