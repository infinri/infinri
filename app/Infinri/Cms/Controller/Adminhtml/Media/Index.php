<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Response;
use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\Helper\PathHelper;

/**
 * Media Manager - Main Gallery View
 * Simple, intuitive media manager for uploading and organizing images.
 */
class Index extends AbstractAdminController
{
    private string $baseUrl = '/media';

    public function __construct(
        \Infinri\Core\App\Request $request,
        Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard
    ) {
        $mediaPath = PathHelper::getMediaPath();
        if (! is_dir($mediaPath)) {
            mkdir($mediaPath, 0755, true);
        }

        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }

    public function execute(): Response
    {
        $currentFolder = $this->getStringParam('folder');
        $mediaPath = PathHelper::getMediaPath();
        $currentPath = $mediaPath . ($currentFolder ? '/' . $currentFolder : '');

        // Security: prevent directory traversal
        $realCurrentPath = realpath($currentPath);
        $realMediaPath = realpath($mediaPath);
        if (false === $realCurrentPath || false === $realMediaPath || ! str_starts_with($realCurrentPath, $realMediaPath)) {
            $currentPath = $mediaPath;
            $currentFolder = '';
        }

        // Get folders and images
        $folders = $this->getFolders($currentPath);
        $images = $this->getImages($currentPath);

        // Generate CSRF tokens
        $csrfTokens = [
            'upload' => $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD),
            'createFolder' => $this->csrfGuard->generateToken(CsrfTokenIds::CREATE_FOLDER),
            'delete' => $this->csrfGuard->generateToken(CsrfTokenIds::DELETE),
        ];

        \Infinri\Core\Helper\Logger::debug('Media Manager: Rendering', [
            'current_folder' => $currentFolder,
            'folders_count' => \count($folders),
            'images_count' => \count($images),
        ]);

        return $this->renderAdminLayout('cms_adminhtml_media_index', [
            'currentFolder' => $currentFolder,
            'folders' => $folders,
            'images' => $images,
            'csrfTokens' => $csrfTokens,
            'breadcrumbs' => $this->generateBreadcrumbs($currentFolder),
        ]);
    }

    private function getFolders(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $folders = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath)) {
                $globResult = glob($fullPath . '/*');
                $folders[] = [
                    'name' => $item,
                    'path' => $fullPath,
                    'count' => \is_array($globResult) ? \count($globResult) : 0,
                ];
            }
        }

        return $folders;
    }

    private function getImages(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $images = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $items = scandir($path);

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_file($fullPath)) {
                $ext = strtolower(pathinfo($item, \PATHINFO_EXTENSION));
                if (\in_array($ext, $allowedExtensions, true)) {
                    $relativePath = str_replace(PathHelper::getMediaPath(), '', $fullPath);
                    $fileSize = filesize($fullPath);
                    $fileTime = filemtime($fullPath);
                    $images[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'url' => $this->baseUrl . $relativePath,
                        'size' => false !== $fileSize ? $fileSize : 0,
                        'modified' => false !== $fileTime ? $fileTime : 0,
                    ];
                }
            }
        }

        // Sort by modified date (newest first)
        usort($images, fn ($a, $b) => $b['modified'] - $a['modified']);

        return $images;
    }

    private function generateBreadcrumbs(string $currentFolder): array
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => '/admin/cms/media/index'],
        ];

        if ($currentFolder) {
            $parts = explode('/', $currentFolder);
            $path = '';

            foreach ($parts as $part) {
                $path .= ($path ? '/' : '') . $part;
                $breadcrumbs[] = [
                    'label' => $part,
                    'url' => '/admin/cms/media/index?folder=' . urlencode($path),
                ];
            }
        }

        return $breadcrumbs;
    }
}
