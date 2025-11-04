<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\JsonResponse;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Security\CsrfGuard;

/**
 * Create New Folder.
 */
class Createfolder
{
    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
    }

    public function execute(Request $request): Response
    {
        try {
            if (! $request->isPost() || ! $this->csrfGuard->validateToken(CsrfTokenIds::CREATE_FOLDER, $request->getParam('_csrf_token'))) {
                return JsonResponse::csrfError();
            }

            $parent = $request->getParam('parent', '');
            $name = $request->getParam('name', '');

            if (empty($name)) {
                throw new \RuntimeException('Folder name is required');
            }

            // Sanitize folder name
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);

            $mediaPath = PathHelper::getMediaPath();
            $parentPath = $mediaPath . ($parent ? '/' . $parent : '');
            $newFolderPath = $parentPath . '/' . $name;

            // Security check
            $realParentPath = realpath($parentPath);
            $realMediaPath = realpath($mediaPath);
            if (false === $realParentPath || false === $realMediaPath || ! str_starts_with($realParentPath, $realMediaPath)) {
                throw new \RuntimeException('Invalid parent path');
            }

            if (is_dir($newFolderPath)) {
                throw new \RuntimeException('Folder already exists');
            }

            mkdir($newFolderPath, 0755, true);

            return JsonResponse::success(['folder' => $name]);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage());
        }
    }
}
