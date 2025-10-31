<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Model\View\LayoutFactory;

/**
 * Media Manager - Main Gallery View
 * 
 * Simple, intuitive media manager for uploading and organizing images
 */
class Index
{
    private string $mediaPath;
    private string $baseUrl = '/media';
    
    public function __construct(
        private readonly CsrfGuard $csrfGuard,
        private readonly LayoutFactory $layoutFactory
    ) {
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
        
        // Generate CSRF tokens
        $csrfTokens = [
            'upload' => $this->csrfGuard->generateToken(CsrfTokenIds::UPLOAD),
            'createFolder' => $this->csrfGuard->generateToken(CsrfTokenIds::CREATE_FOLDER),
            'delete' => $this->csrfGuard->generateToken(CsrfTokenIds::DELETE),
        ];
        
        // Render using layout system
        $html = $this->layoutFactory->render('cms_adminhtml_media_index', [
            'currentFolder' => $currentFolder,
            'folders' => $folders,
            'images' => $images,
            'csrfTokens' => $csrfTokens,
            'breadcrumbs' => $this->generateBreadcrumbs($currentFolder),
        ]);
        
        \Infinri\Core\Helper\Logger::debug('Media Manager: HTML rendered', [
            'html_length' => strlen($html),
            'html_preview' => substr($html, 0, 200)
        ]);
        
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
