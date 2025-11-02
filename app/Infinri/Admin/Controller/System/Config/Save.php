<?php
declare(strict_types=1);

namespace Infinri\Admin\Controller\System\Config;

use Infinri\Core\Controller\AbstractAdminController;
use Infinri\Core\App\Response;
use Infinri\Core\Model\Config;
use Infinri\Core\Model\Message\MessageManager;

/**
 * System Configuration Save Controller
 */
class Save extends AbstractAdminController
{
    public function __construct(
        \Infinri\Core\App\Request $request,
        \Infinri\Core\App\Response $response,
        \Infinri\Core\Model\View\LayoutFactory $layoutFactory,
        \Infinri\Core\Security\CsrfGuard $csrfGuard,
        private readonly Config $config,
        private readonly MessageManager $messageManager
    ) {
        parent::__construct($request, $response, $layoutFactory, $csrfGuard);
    }
    
    /**
     * Save configuration
     */
    public function execute(): Response
    {
        $section = $this->getStringParam('section', 'general');
        $groups = $this->request->getParam('groups', []);
        
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
        return $this->redirectToRoute('/admin/system/config/index', ['section' => $section]);
    }
}
