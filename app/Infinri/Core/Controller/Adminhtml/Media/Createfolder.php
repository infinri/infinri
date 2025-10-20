<?php

declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Media;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;

/**
 * Create New Folder
 */
class Createfolder
{
    private string $mediaPath;
    
    public function __construct()
    {
        $this->mediaPath = dirname(__DIR__, 6) . '/pub/media';
    }

    public function execute(Request $request): Response
    {
        $response = new Response();
        
        try {
            $response->setHeader('Content-Type', 'application/json');
            $parent = $request->getParam('parent', '');
            $name = $request->getParam('name', '');
            
            if (empty($name)) {
                throw new \RuntimeException('Folder name is required');
            }
            
            // Sanitize folder name
            $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
            
            $parentPath = $this->mediaPath . ($parent ? '/' . $parent : '');
            $newFolderPath = $parentPath . '/' . $name;
            
            // Security check
            if (strpos(realpath($parentPath), realpath($this->mediaPath)) !== 0) {
                throw new \RuntimeException('Invalid parent path');
            }
            
            if (is_dir($newFolderPath)) {
                throw new \RuntimeException('Folder already exists');
            }
            
            mkdir($newFolderPath, 0755, true);
            
            $response->setBody(json_encode([
                'success' => true,
                'folder' => $name
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
