<?php

declare(strict_types=1);

namespace Infinri\Core\Model;

use Infinri\Core\Model\ResourceModel\AbstractResource;
use Infinri\Core\Model\ResourceModel\User as UserResource;

/**
 * Example model implementation.
 */
class User extends AbstractModel
{
    public function __construct(
        private readonly UserResource $resource,
        array $data = []
    ) {
        parent::__construct($data);
    }

    protected function getResource(): AbstractResource
    {
        return $this->resource;
    }

    /**
     * Get user email.
     */
    public function getEmail(): ?string
    {
        return $this->getData('email');
    }

    /**
     * Set user email.
     *
     * @return $this
     */
    public function setEmail(string $email): self
    {
        return $this->setData('email', $email);
    }

    /**
     * Get user name.
     */
    public function getName(): ?string
    {
        return $this->getData('name');
    }

    /**
     * Set user name.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        return $this->setData('name', $name);
    }

    /**
     * Get created at timestamp.
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }
}
