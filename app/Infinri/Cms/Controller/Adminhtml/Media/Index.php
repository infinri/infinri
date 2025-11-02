<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Helper\PathHelper;

/**
 * Media Manager - Main Gallery View
 * 
 * Simple, intuitive media manager for uploading and organizing images
 * 
 * Phase 4: DRY/KISS - Uses PathHelper
 */
class Index extends AbstractAdminController
{
    private string $baseUrl = '/media';
    
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
        
        $mediaPath = PathHelper::getMediaPath();
        if (!is_dir($mediaPath)) {
            mkdir($mediaPath, 0755, true);
        }
    }

    public function execute(): Response
    {
        $currentFolder = $this->getStringParam('folder');
        $mediaPath = PathHelper::getMediaPath();
        $currentPath = $mediaPath . ($currentFolder ? '/' . $currentFolder : '');
        
        // Security: prevent directory traversal
        if (strpos(realpath($currentPath), realpath($mediaPath)) !== 0) {
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
            'folders_count' => count($folders),
            'images_count' => count($images)
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
                    $relativePath = str_replace(PathHelper::getMediaPath(), '', $fullPath);
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
    
    private function generateBreadcrumbs(string $currentFolder): array
    {
        $breadcrumbs = [
            ['label' => 'Home', 'url' => '/admin/cms/media/index']
        ];
        
        if ($currentFolder) {
            $parts = explode('/', $currentFolder);
            $path = '';
            
            foreach ($parts as $part) {
                $path .= ($path ? '/' : '') . $part;
                $breadcrumbs[] = [
                    'label' => $part,
                    'url' => '/admin/cms/media/index?folder=' . urlencode($path)
                ];
            }
        }
        
        return $breadcrumbs;
    }
}
