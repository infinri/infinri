<?php

declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Cms\Model\ResourceModel\Page as PageResource;

/**
 * CMS Page Model
 * 
 * Represents a CMS page entity.
 * Now extends AbstractContentEntity for shared functionality with other content types.
 */
class Page extends AbstractContentEntity
{
    /**
     * Homepage ID - cannot be deleted
     */
    public const HOMEPAGE_ID = 1;
    
    /**
     * Constructor
     *
     * @param PageResource $resource
     * @param array $data
     */
    public function __construct(
        PageResource $resource,
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
        return 'url_key';
    }

    /**
     * Get entity type (implements abstract method)
     *
     * @return string
     */
    protected function getEntityType(): string
    {
        return 'page';
    }
    
    // Note: getId() is inherited from AbstractModel and uses page_id field

    // ==================== PAGE-SPECIFIC METHODS ====================

    /**
     * Get page ID
     *
     * @return int|null
     */
    public function getPageId(): ?int
    {
        return $this->getData('page_id');
    }

    /**
     * Set page ID
     *
     * @param int $id
     * @return $this
     */
    public function setPageId(int $id): self
    {
        return $this->setData('page_id', $id);
    }

    /**
     * Get URL key
     *
     * @return string|null
     */
    public function getUrlKey(): ?string
    {
        return $this->getData('url_key');
    }

    /**
     * Set URL key
     *
     * @param string $urlKey
     * @return $this
     */
    public function setUrlKey(string $urlKey): self
    {
        return $this->setData('url_key', $urlKey);
    }

    /**
     * Get meta title
     *
     * @return string|null
     */
    public function getMetaTitle(): ?string
    {
        return $this->getData('meta_title');
    }

    /**
     * Set meta title
     *
     * @param string $metaTitle
     * @return $this
     */
    public function setMetaTitle(string $metaTitle): self
    {
        return $this->setData('meta_title', $metaTitle);
    }

    /**
     * Get meta description
     *
     * @return string|null
     */
    public function getMetaDescription(): ?string
    {
        return $this->getData('meta_description');
    }

    /**
     * Set meta description
     *
     * @param string $metaDescription
     * @return $this
     */
    public function setMetaDescription(string $metaDescription): self
    {
        return $this->setData('meta_description', $metaDescription);
    }

    /**
     * Get meta keywords
     *
     * @return string|null
     */
    public function getMetaKeywords(): ?string
    {
        return $this->getData('meta_keywords');
    }

    /**
     * Set meta keywords
     *
     * @param string $metaKeywords
     * @return $this
     */
    public function setMetaKeywords(string $metaKeywords): self
    {
        return $this->setData('meta_keywords', $metaKeywords);
    }

    /**
     * Check if page is homepage
     *
     * @return bool
     */
    public function isHomepage(): bool
    {
        return (bool) $this->getData('is_homepage');
    }

    /**
     * Set homepage flag
     *
     * @param bool $isHomepage
     * @return $this
     */
    public function setIsHomepage(bool $isHomepage): self
    {
        return $this->setData('is_homepage', $isHomepage);
    }
    
    // Note: Common methods (getTitle, setTitle, getContent, setContent, 
    // isActive, setIsActive, getCreatedAt, getUpdatedAt, validate) are now 
    // inherited from AbstractContentEntity
}
