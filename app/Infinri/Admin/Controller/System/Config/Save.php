<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\Config;
use Infinri\Core\Model\Message\MessageManager;

/**
 * System Configuration Save Controller
 */
class Save
{
    public function __construct(
        private readonly Config $config,
        private readonly MessageManager $messageManager
    ) {
    }
    
    /**
     * Save configuration
     */
    public function execute(Request $request): Response
    {
        $section = $request->getParam('section', 'general');
        $groups = $request->getParam('groups', []);
        
        try {
            // Process each group's fields
            foreach ($groups as $groupId => $groupData) {
                if (!isset($groupData['fields'])) {
                    continue;
                }
                
                foreach ($groupData['fields'] as $fieldId => $fieldData) {
                    $value = $fieldData['value'] ?? null;
                    $path = $section . '/' . $groupId . '/' . $fieldId;
                    
                    if ($value !== null) {
                        $this->config->saveValue($path, $value);
                    }
                }
            }
            
            // Add success message
            $this->messageManager->addSuccess('Configuration saved successfully.');
            
        } catch (\Exception $e) {
            // Add error message
            $this->messageManager->addError('Failed to save configuration: ' . $e->getMessage());
        }
        
        // Redirect back to configuration page
        $response = new Response();
        $response->redirect('/admin/system/config/index?section=' . urlencode($section));
        return $response;
    }
}
