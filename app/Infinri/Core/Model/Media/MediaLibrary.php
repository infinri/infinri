<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Media;

/**
 * Handles media file operations (scanning, validation, metadata)
 */
class MediaLibrary
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function __construct(
        private readonly string $mediaPath,
        private readonly string $baseUrl = '/media'
    ) {
        if (!is_dir($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }
    }

    /**
     * Get folders in a directory
     *
     * @param string $relativePath Relative path from media root
     * @return array Array of folder info
     */
    public function getFolders(string $relativePath = ''): array
    {
        $fullPath = $this->getFullPath($relativePath);

        if (!is_dir($fullPath)) {
            return [];
        }

        $folders = [];
        foreach (scandir($fullPath) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            if (is_dir($itemPath)) {
                $folders[] = [
                    'name' => $item,
                    'count' => count(glob($itemPath . '/*')),
                ];
            }
        }

        return $folders;
    }

    /**
     * Get images in a directory
     *
     * @param string $relativePath Relative path from media root
     * @return FileInfo[] Array of FileInfo objects
     */
    public function getImages(string $relativePath = ''): array
    {
        $fullPath = $this->getFullPath($relativePath);

        if (!is_dir($fullPath)) {
            return [];
        }

        $images = [];

        foreach (scandir($fullPath) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            if (!is_file($itemPath)) {
                continue;
            }

            $fileInfo = $this->getFileInfo($itemPath, $relativePath);

            if ($fileInfo && $fileInfo->isImage()) {
                $images[] = $fileInfo;
            }
        }

        // Sort by modified time (newest first)
        usort($images, static fn(FileInfo $a, FileInfo $b) => $b->modifiedTime <=> $a->modifiedTime);

        return $images;
    }

    /**
     * Get file information
     *
     * @param string $fullPath Full filesystem path
     * @param string $relativePath Relative path from media root
     * @return FileInfo|null
     */
    public function getFileInfo(string $fullPath, string $relativePath = ''): ?FileInfo
    {
        if (!is_file($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        // Validate extension
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $name = basename($fullPath);
        $relativeFilePath = str_replace($this->mediaPath, '', $fullPath);
        $url = $this->baseUrl . $relativeFilePath;

        return new FileInfo(
            name: $name,
            path: $fullPath,
            url: $url,
            size: filesize($fullPath) ?: 0,
            extension: $extension,
            modifiedTime: filemtime($fullPath) ?: time()
        );
    }

    /**
     * Check if file is a valid image
     *
     * @param string $fullPath Full filesystem path
     * @return bool
     */
    public function isValidImage(string $fullPath): bool
    {
        if (!is_file($fullPath)) {
            return false;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Get full filesystem path from relative path
     *
     * @param string $relativePath Relative path from media root
     * @return string Full filesystem path
     */
    private function getFullPath(string $relativePath): string
    {
        if ($relativePath === '') {
            return $this->mediaPath;
        }

        // Security: Prevent directory traversal
        $relativePath = str_replace(['../', '..\\'], '', $relativePath);

        return $this->mediaPath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Get base media path
     *
     * @return string
     */
    public function getMediaPath(): string
    {
        return $this->mediaPath;
    }

    /**
     * Get base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
