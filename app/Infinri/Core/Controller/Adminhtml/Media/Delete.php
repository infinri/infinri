<?php

declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Controller\Adminhtml\Media\CsrfTokenIds;
use Infinri\Core\Security\CsrfGuard;

/**
 * Delete Image
 */
class Delete
{
    private string $mediaPath;
    
    public function __construct(private readonly CsrfGuard $csrfGuard)
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
            $file = $input['file'] ?? '';
            $folder = $input['folder'] ?? '';
            $token = $input['_csrf_token'] ?? null;

            if (!$request->isPost() || !$this->csrfGuard->validateToken(CsrfTokenIds::DELETE, is_string($token) ? $token : null)) {
                $response->setForbidden();
                return $response->setBody(json_encode([
                    'success' => false,
                    'error' => 'Invalid CSRF token'
                ]));
            }
            
            if (empty($file)) {
                throw new \RuntimeException('File name is required');
            }
            
            $filePath = $this->mediaPath . ($folder ? '/' . $folder : '') . '/' . $file;
            
            // Security check
            if (strpos(realpath(dirname($filePath)), realpath($this->mediaPath)) !== 0) {
                throw new \RuntimeException('Invalid file path');
            }
            
            if (!is_file($filePath)) {
                throw new \RuntimeException('File not found');
            }
            
            unlink($filePath);
            
            $response->setBody(json_encode([
                'success' => true
            ]));

        } catch (\JsonException $e) {
            $response->setServerError();
            $response->setBody(json_encode([
                'success' => false,
                'error' => 'Invalid JSON payload'
            ]));
        } catch (\Throwable $e) {
            $response->setServerError();
            $response->setBody(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }

        return $response;
    }
}
