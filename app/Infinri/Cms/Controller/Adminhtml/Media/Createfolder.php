<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Cms\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Helper\JsonResponse;

/**
 * Create New Folder
 * 
 * Phase 4: DRY/KISS - Uses PathHelper and JsonResponse
 */
class Createfolder
{
    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
    }

    public function execute(Request $request): Response
    {
        try {
            if (!$request->isPost() || !$this->csrfGuard->validateToken(CsrfTokenIds::CREATE_FOLDER, $request->getParam('_csrf_token'))) {
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
            if (strpos(realpath($parentPath), realpath($mediaPath)) !== 0) {
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
