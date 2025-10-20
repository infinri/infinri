<?php

declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Delete Image
 */
class Delete
{
    private string $mediaPath;
    
    public function __construct()
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $file = $input['file'] ?? '';
            $folder = $input['folder'] ?? '';
            
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
