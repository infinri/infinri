<?php
declare(strict_types=1);

namespace Infinri\Seo\Ui\Component\Listing;

use Infinri\Seo\Model\Repository\RedirectRepository;

/**
 * Redirect Listing DataProvider
 */
class RedirectDataProvider
{
    public function __construct(
        private RedirectRepository $redirectRepository
    ) {}

    /**
     * Get redirect data for grid
     */
    public function getData(): array
    {
        $redirects = $this->redirectRepository->getAll();

        $items = [];
        foreach ($redirects as $redirect) {
            $items[] = [
                'redirect_id' => $redirect->getRedirectId(),
                'from_path' => $redirect->getFromPath(),
                'to_path' => $redirect->getToPath(),
                'redirect_code' => $redirect->getRedirectCode(),
                'description' => $redirect->getDescription(),
                'is_active' => $redirect->isActive() ? 1 : 0,
                'priority' => $redirect->getData('priority'),
                'created_at' => $redirect->getData('created_at'),
                'updated_at' => $redirect->getData('updated_at'),
            ];
        }

        return [
            'totalRecords' => count($items),
            'items' => $items
        ];
    }
}
