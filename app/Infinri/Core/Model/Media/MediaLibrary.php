<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Media;

/**
 * Handles media file operations (scanning, validation, metadata).
 */
class MediaLibrary
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function __construct(
        private readonly string $mediaPath,
        private readonly string $baseUrl = '/media'
    ) {
        if (! is_dir($this->mediaPath)) {
            mkdir($this->mediaPath, 0755, true);
        }
    }

    /**
     * Get folders in a directory.
     *
     * @param string $relativePath Relative path from media root
     *
     * @return array Array of folder info
     */
    public function getFolders(string $relativePath = ''): array
    {
        $fullPath = $this->getFullPath($relativePath);

        if (! is_dir($fullPath)) {
            return [];
        }

        $folders = [];
        foreach (scandir($fullPath) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            if (is_dir($itemPath)) {
                $globResult = glob($itemPath . '/*');
                $folders[] = [
                    'name' => $item,
                    'count' => \is_array($globResult) ? \count($globResult) : 0,
                ];
            }
        }

        return $folders;
    }

    /**
     * Get images in a directory.
     *
     * @param string $relativePath Relative path from media root
     *
     * @return FileInfo[] Array of FileInfo objects
     */
    public function getImages(string $relativePath = ''): array
    {
        $fullPath = $this->getFullPath($relativePath);

        if (! is_dir($fullPath)) {
            return [];
        }

        $images = [];

        foreach (scandir($fullPath) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = $fullPath . '/' . $item;
            if (! is_file($itemPath)) {
                continue;
            }

            $fileInfo = $this->getFileInfo($itemPath, $relativePath);

            if ($fileInfo && $fileInfo->isImage()) {
                $images[] = $fileInfo;
            }
        }

        // Sort by modified time (newest first)
        usort($images, static fn (FileInfo $a, FileInfo $b) => $b->modifiedTime <=> $a->modifiedTime);

        return $images;
    }

    /**
     * Get file information.
     *
     * @param string $fullPath     Full filesystem path
     * @param string $relativePath Relative path from media root
     */
    public function getFileInfo(string $fullPath, string $relativePath = ''): ?FileInfo
    {
        if (! is_file($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, \PATHINFO_EXTENSION));

        // Validate extension
        if (! \in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
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
     * Check if file is a valid image.
     *
     * @param string $fullPath Full filesystem path
     */
    public function isValidImage(string $fullPath): bool
    {
        if (! is_file($fullPath)) {
            return false;
        }

        $extension = strtolower(pathinfo($fullPath, \PATHINFO_EXTENSION));

        return \in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    /**
     * Get full filesystem path from relative path.
     *
     * @param string $relativePath Relative path from media root
     *
     * @return string Full filesystem path
     */
    private function getFullPath(string $relativePath): string
    {
        if ('' === $relativePath) {
            return $this->mediaPath;
        }

        // Security: Prevent directory traversal
        $relativePath = str_replace(['../', '..\\'], '', $relativePath);

        return $this->mediaPath . '/' . ltrim($relativePath, '/');
    }

    /**
     * Count total media files recursively.
     *
     * @param string $relativePath Relative path from media root (empty for all)
     *
     * @return int Total count of media files
     */
    public function countFiles(string $relativePath = ''): int
    {
        $fullPath = $this->getFullPath($relativePath);

        if (! is_dir($fullPath)) {
            return 0;
        }

        return $this->countFilesRecursive($fullPath);
    }

    /**
     * Recursively count files in directory.
     *
     * @param string $path Directory path
     *
     * @return int File count
     */
    private function countFilesRecursive(string $path): int
    {
        $count = 0;

        foreach (scandir($path) ?: [] as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (is_dir($itemPath)) {
                $count += $this->countFilesRecursive($itemPath);
            } elseif (is_file($itemPath)) {
                // Check if it's a valid media file
                $extension = strtolower(pathinfo($itemPath, \PATHINFO_EXTENSION));
                if (\in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Get base media path.
     */
    public function getMediaPath(): string
    {
        return $this->mediaPath;
    }

    /**
     * Get base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
