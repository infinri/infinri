<?php
declare(strict_types=1);

namespace Infinri\Seo\Ui\Component\Form;

use Infinri\Seo\Model\Repository\RedirectRepository;

/**
 * Redirect Form DataProvider
 */
class RedirectDataProvider
{
    public function __construct(
        private RedirectRepository $redirectRepository
    ) {}

    /**
     * Get redirect data for form
     */
    public function getData(?int $redirectId = null): array
    {
        if ($redirectId === null) {
            // New redirect - return defaults
            return [
                'redirect_code' => 301,
                'is_active' => 1,
                'priority' => 0
            ];
        }

        $redirect = $this->redirectRepository->getById($redirectId);
        
        if (!$redirect) {
            return [];
        }

        return [
            'redirect_id' => $redirect->getRedirectId(),
            'from_path' => $redirect->getFromPath(),
            'to_path' => $redirect->getToPath(),
            'redirect_code' => $redirect->getRedirectCode(),
            'description' => $redirect->getDescription(),
            'is_active' => $redirect->isActive() ? 1 : 0,
            'priority' => $redirect->getData('priority'),
        ];
    }
}
