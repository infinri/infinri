<?php

declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Cms\Model\ResourceModel\Block as BlockResource;

/**
 * CMS Block Model
 *
 * Represents a reusable content block (similar to Magento static blocks).
 * 
 * Now extends AbstractContentEntity for shared functionality with other content types.
 */
class Block extends AbstractContentEntity
{
    /**
     * Constructor
     *
     * @param BlockResource $resource
     * @param array $data
     */
    public function __construct(
        BlockResource $resource,
        array $data = []
    ) {
        parent::__construct($resource, $data);
    }

    // ==================== REQUIRED ABSTRACT METHODS ====================

    /**
     * Get identifier field name (implements abstract method)
     *
     * @return string
     */
    protected function getIdentifierField(): string
    {
        return 'identifier';
    }

    /**
     * Get entity type (implements abstract method)
     *
     * @return string
     */
    protected function getEntityType(): string
    {
        return 'block';
    }
    
    // Note: getId() is inherited from AbstractModel and uses block_id field

    // ==================== BLOCK-SPECIFIC METHODS ====================

    /**
     * Get block ID
     *
     * @return int|null
     */
    public function getBlockId(): ?int
    {
        return $this->getData('block_id');
    }

    /**
     * Set block ID
     *
     * @param int $blockId
     * @return self
     */
    public function setBlockId(int $blockId): self
    {
        return $this->setData('block_id', $blockId);
    }

    /**
     * Get identifier (unique key for referencing blocks in layouts)
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->getData('identifier');
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return self
     */
    public function setIdentifier(string $identifier): self
    {
        return $this->setData('identifier', $identifier);
    }

    // Note: Common methods (getTitle, setTitle, getContent, setContent, 
    // isActive, setIsActive, getCreatedAt, getUpdatedAt) are now 
    // inherited from AbstractContentEntity
}
