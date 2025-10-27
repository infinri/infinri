<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\Config;

/**
 * System Configuration Save Controller
 */
class Save
{
    private Config $config;
    
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }
    
    /**
     * Save configuration
     */
    public function execute(Request $request): Response
    {
        $section = $request->getParam('section', 'general');
        $groups = $request->getParam('groups', []);
        
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
        
        // Redirect back to configuration page with success message
        $response = new Response();
        $response->redirect('/admin/system/config/index?section=' . urlencode($section));
        return $response;
    }
}
