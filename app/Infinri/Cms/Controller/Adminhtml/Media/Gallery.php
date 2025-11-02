<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Helper\JsonResponse;

/**
 * List Images from Media Gallery
 * 
 * Phase 4: DRY/KISS - Uses PathHelper and JsonResponse
 */
class Gallery
{
    public function __construct()
    {
    }

    public function execute(Request $request): Response
    {
        try {
            $images = $this->scanForImages(PathHelper::getMediaPath());
            return JsonResponse::success(['images' => $images]);
            
        } catch (\Throwable $e) {
            error_log('Gallery exception: ' . $e->getMessage());
            return JsonResponse::error($e->getMessage());
        }
    }
    
    /**
     * Recursively scan for images in media directory
     */
    private function scanForImages(string $path, string $relativePath = ''): array
    {
        $images = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!is_dir($path)) {
            return $images;
        }
        
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep') {
                continue;
            }
            
            $fullPath = $path . '/' . $item;
            $itemRelativePath = $relativePath ? $relativePath . '/' . $item : $item;
            
            if (is_dir($fullPath)) {
                // Recursively scan subdirectories
                $images = array_merge($images, $this->scanForImages($fullPath, $itemRelativePath));
            } elseif (is_file($fullPath)) {
                $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                
                if (in_array($extension, $allowedExtensions)) {
                    $images[] = [
                        'url' => '/media/' . $itemRelativePath,
                        'name' => $item,
                        'path' => $itemRelativePath,
                        'size' => filesize($fullPath),
                        'modified' => filemtime($fullPath)
                    ];
                }
            }
        }
        
        return $images;
    }
}
