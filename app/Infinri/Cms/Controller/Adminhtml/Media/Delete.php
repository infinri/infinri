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
 * Delete Image
 */
class Delete
{
    public function __construct(private readonly CsrfGuard $csrfGuard) {}

    public function execute(Request $request): Response
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            $file = $input['file'] ?? '';
            $folder = $input['folder'] ?? '';
            $token = $input['_csrf_token'] ?? null;

            if (!$request->isPost() || !$this->csrfGuard->validateToken(CsrfTokenIds::DELETE, is_string($token) ? $token : null)) {
                return JsonResponse::csrfError();
            }

            if (empty($file)) {
                throw new \RuntimeException('File name is required');
            }

            $mediaPath = PathHelper::getMediaPath();
            $filePath = $mediaPath . ($folder ? '/' . $folder : '') . '/' . $file;

            // Security check
            if (strpos(realpath(dirname($filePath)), realpath($mediaPath)) !== 0) {
                throw new \RuntimeException('Invalid file path');
            }

            if (!is_file($filePath)) {
                throw new \RuntimeException('File not found');
            }

            unlink($filePath);

            return JsonResponse::success();

        } catch (\JsonException $e) {
            return JsonResponse::error('Invalid JSON payload', 400);
        } catch (\Throwable $e) {
            return JsonResponse::error($e->getMessage());
        }
    }
}
