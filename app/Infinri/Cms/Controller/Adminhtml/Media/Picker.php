<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Model\Media\MediaLibrary;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;

/**
 * Media Picker Controller
 */
class Picker extends AbstractAdminController
{
    private MediaLibrary $mediaLibrary;

    public function __construct(
        \Infinri\Core\App\Request              $request,
        \Infinri\Core\App\Response             $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard       $csrfGuard
    ) {
        $mediaPath = dirname(__DIR__, 6) . '/pub/media';
        $this->mediaLibrary = new MediaLibrary($mediaPath, '/media');

        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        $csrfToken = $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD);

        $templatePath = dirname(__DIR__, 3) . '/view/adminhtml/templates/media/picker.phtml';

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $html = ob_get_clean();
        } else {
            $html = '<p>Error: Picker template not found</p>';
        }

        return $this->response->setBody($html);
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
}
