<?php

declare(strict_types=1);

namespace Infinri\Cms\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Helper\JsonResponse;
use Infinri\Core\Helper\PathHelper;
use Infinri\Core\Security\CsrfGuard;

/**
 * Delete Image.
 */
class Delete
{
    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
    }

    public function execute(Request $request): Response
    {
        try {
            $rawInput = file_get_contents('php://input');
            if (false === $rawInput) {
                throw new \RuntimeException('Failed to read request body');
            }
            $input = json_decode($rawInput, true, 512, \JSON_THROW_ON_ERROR);
            $file = $input['file'] ?? '';
            $folder = $input['folder'] ?? '';
            $token = $input['_csrf_token'] ?? null;

            if (! $request->isPost() || ! $this->csrfGuard->validateToken(CsrfTokenIds::DELETE, \is_string($token) ? $token : null)) {
                return JsonResponse::csrfError();
            }

            if (empty($file)) {
                throw new \RuntimeException('File name is required');
            }

            $mediaPath = PathHelper::getMediaPath();
            $filePath = $mediaPath . ($folder ? '/' . $folder : '') . '/' . $file;

            // Security check
            $realDirPath = realpath(\dirname($filePath));
            $realMediaPath = realpath($mediaPath);
            if (false === $realDirPath || false === $realMediaPath || ! str_starts_with($realDirPath, $realMediaPath)) {
                throw new \RuntimeException('Invalid file path');
            }

            if (! is_file($filePath)) {
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
