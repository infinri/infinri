<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Helper\JsonResponse;
use Infinri\Core\Helper\Logger;

/**
 * List Images from Media Gallery
 */
class Gallery extends AbstractAdminController
{
    public function execute(): Response
    {
        try {
            $images = $this->scanForImages(PathHelper::getMediaPath());
            return JsonResponse::success(['images' => $images]);

        } catch (\Throwable $e) {
            Logger::error('Gallery exception', ['error' => $e->getMessage()]);
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

                if (in_array($extension, $allowedExtensions, true)) {
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
