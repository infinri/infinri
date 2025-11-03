<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Media;

/**
 * Represents file metadata
 */
class FileInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly string $url,
        public readonly int    $size,
        public readonly string $extension,
        public readonly int    $modifiedTime
    ) {}

    /**
     * Get formatted file size
     *
     * @return string Human-readable size (e.g., "2.5 MB")
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     *
     * @return bool
     */
    public function isImage(): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        return in_array(strtolower($this->extension), $imageExtensions, true);
    }

    /**
     * Get file data as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'size' => $this->size,
            'extension' => $this->extension,
            'modified' => $this->modifiedTime,
            'formattedSize' => $this->getFormattedSize(),
        ];
    }
}
