<?php

declare(strict_types=1);

namespace Infinri\Seo\Service;

use Infinri\Core\Model\ObjectManager;
use Infinri\Seo\Model\Redirect;
use Infinri\Seo\Model\Repository\RedirectRepository;
use Psr\Log\LoggerInterface;

/**
 * Handles redirect creation, validation, and management.
 */
class RedirectManager
{
    public function __construct(
        private RedirectRepository $redirectRepository,
        private ObjectManager $objectManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create new redirect.
     */
    public function createRedirect(
        string $fromPath,
        string $toPath,
        int $redirectCode = 301,
        ?string $description = null,
        bool $isActive = true,
        int $priority = 0
    ): Redirect {
        // Normalize paths
        $fromPath = $this->normalizePath($fromPath);
        $toPath = $this->normalizePath($toPath);

        /** @var Redirect $redirect */
        $redirect = $this->objectManager->create(Redirect::class);
        $redirect->setFromPath($fromPath);
        $redirect->setToPath($toPath);
        $redirect->setRedirectCode($redirectCode);
        $redirect->setDescription($description);
        $redirect->setIsActive($isActive);
        $redirect->setData('priority', $priority);

        $this->redirectRepository->save($redirect);

        $this->logger->info('Redirect created', [
            'from_path' => $fromPath,
            'to_path' => $toPath,
            'redirect_code' => $redirectCode,
        ]);

        return $redirect;
    }

    /**
     * Update existing redirect.
     *
     * @param array<string, mixed> $data
     */
    public function updateRedirect(
        int $redirectId,
        array $data
    ): ?Redirect {
        $redirect = $this->redirectRepository->getById($redirectId);

        if (! $redirect) {
            return null;
        }

        if (isset($data['from_path'])) {
            $redirect->setFromPath($this->normalizePath($data['from_path']));
        }

        if (isset($data['to_path'])) {
            $redirect->setToPath($this->normalizePath($data['to_path']));
        }

        if (isset($data['redirect_code'])) {
            $redirect->setRedirectCode((int) $data['redirect_code']);
        }

        if (isset($data['description'])) {
            $redirect->setDescription($data['description']);
        }

        if (isset($data['is_active'])) {
            $redirect->setIsActive((bool) $data['is_active']);
        }

        if (isset($data['priority'])) {
            $redirect->setData('priority', (int) $data['priority']);
        }

        $this->redirectRepository->save($redirect);

        $this->logger->info('Redirect updated', [
            'redirect_id' => $redirectId,
        ]);

        return $redirect;
    }

    /**
     * Delete redirect.
     */
    public function deleteRedirect(int $redirectId): bool
    {
        $result = $this->redirectRepository->deleteById($redirectId);

        if ($result) {
            $this->logger->info('Redirect deleted', [
                'redirect_id' => $redirectId,
            ]);
        }

        return $result;
    }

    /**
     * Check if redirect exists for path.
     */
    public function findRedirectForPath(string $path): ?Redirect
    {
        $normalizedPath = $this->normalizePath($path);

        return $this->redirectRepository->findByFromPath($normalizedPath);
    }

    /**
     * Validate redirect data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<int, string>
     */
    public function validateRedirectData(array $data): array
    {
        $errors = [];

        if (empty($data['from_path'])) {
            $errors[] = 'From path is required';
        }

        if (empty($data['to_path'])) {
            $errors[] = 'To path is required';
        }

        if (isset($data['redirect_code']) && ! \in_array($data['redirect_code'], [301, 302], true)) {
            $errors[] = 'Redirect code must be 301 or 302';
        }

        // Check for circular redirects
        if (! empty($data['from_path']) && ! empty($data['to_path'])) {
            $fromPath = $this->normalizePath($data['from_path']);
            $toPath = $this->normalizePath($data['to_path']);

            if ($fromPath === $toPath) {
                $errors[] = 'Cannot redirect a path to itself';
            }
        }

        return $errors;
    }

    /**
     * Normalize path (remove leading/trailing slashes, ensure consistency).
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        return strtolower($path);
    }

    /**
     * Get all redirects.
     */
    public function getAllRedirects(): array
    {
        return $this->redirectRepository->getAll();
    }

    /**
     * Get active redirects.
     */
    public function getActiveRedirects(): array
    {
        return $this->redirectRepository->getAllActive();
    }
}
