<?php
declare(strict_types=1);

namespace Infinri\Seo\Model;

use Infinri\Core\Model\AbstractModel;
use Infinri\Core\Model\ResourceModel\AbstractResource;
use Infinri\Seo\Model\ResourceModel\Redirect as RedirectResource;

/**
 * Redirect Model
 * 
 * Represents a 301/302 redirect rule
 */
class Redirect extends AbstractModel
{
    /**
     * Constructor
     */
    public function __construct(
        protected readonly RedirectResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * Get resource model
     */
    protected function getResource(): AbstractResource
    {
        return $this->resource;
    }
    /**
     * Get redirect ID
     */
    public function getRedirectId(): ?int
    {
        return $this->getData('redirect_id');
    }

    /**
     * Set redirect ID
     */
    public function setRedirectId(int $redirectId): self
    {
        return $this->setData('redirect_id', $redirectId);
    }

    /**
     * Get from path
     */
    public function getFromPath(): ?string
    {
        return $this->getData('from_path');
    }

    /**
     * Set from path
     */
    public function setFromPath(string $fromPath): self
    {
        return $this->setData('from_path', $fromPath);
    }

    /**
     * Get to path
     */
    public function getToPath(): ?string
    {
        return $this->getData('to_path');
    }

    /**
     * Set to path
     */
    public function setToPath(string $toPath): self
    {
        return $this->setData('to_path', $toPath);
    }

    /**
     * Get redirect code (301 or 302)
     */
    public function getRedirectCode(): ?int
    {
        return $this->getData('redirect_code');
    }

    /**
     * Set redirect code
     */
    public function setRedirectCode(int $redirectCode): self
    {
        return $this->setData('redirect_code', $redirectCode);
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    /**
     * Set description
     */
    public function setDescription(?string $description): self
    {
        return $this->setData('description', $description);
    }

    /**
     * Is active
     */
    public function isActive(): bool
    {
        return (bool)$this->getData('is_active');
    }

    /**
     * Set is active
     */
    public function setIsActive(bool $isActive): self
    {
        return $this->setData('is_active', $isActive);
    }
}
