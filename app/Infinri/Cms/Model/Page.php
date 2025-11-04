<?php

declare(strict_types=1);

namespace Infinri\Cms\Model;

use Infinri\Cms\Model\ResourceModel\Page as PageResource;

/**
 * Represents a CMS page entity.
 */
class Page extends AbstractContentEntity
{
    /**
     * Homepage ID - cannot be deleted.
     */
    public const HOMEPAGE_ID = 1;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        PageResource $resource,
        array $data = []
    ) {
        parent::__construct($resource, $data);
    }

    /**
     * Get identifier field name (implements abstract method).
     */
    protected function getIdentifierField(): string
    {
        return 'url_key';
    }

    /**
     * Get entity type (implements abstract method).
     */
    protected function getEntityType(): string
    {
        return 'page';
    }

    /**
     * Get page ID.
     */
    public function getPageId(): ?int
    {
        return $this->getData('page_id');
    }

    /**
     * Set page ID.
     *
     * @return $this
     */
    public function setPageId(int $id): self
    {
        return $this->setData('page_id', $id);
    }

    /**
     * Get URL key.
     */
    public function getUrlKey(): ?string
    {
        return $this->getData('url_key');
    }

    /**
     * Set URL key.
     *
     * @return $this
     */
    public function setUrlKey(string $urlKey): self
    {
        return $this->setData('url_key', $urlKey);
    }

    /**
     * Get meta title.
     */
    public function getMetaTitle(): ?string
    {
        return $this->getData('meta_title');
    }

    /**
     * Set meta title.
     *
     * @return $this
     */
    public function setMetaTitle(string $metaTitle): self
    {
        return $this->setData('meta_title', $metaTitle);
    }

    /**
     * Get meta description.
     */
    public function getMetaDescription(): ?string
    {
        return $this->getData('meta_description');
    }

    /**
     * Set meta description.
     *
     * @return $this
     */
    public function setMetaDescription(string $metaDescription): self
    {
        return $this->setData('meta_description', $metaDescription);
    }

    /**
     * Get meta keywords.
     */
    public function getMetaKeywords(): ?string
    {
        return $this->getData('meta_keywords');
    }

    /**
     * Set meta keywords.
     *
     * @return $this
     */
    public function setMetaKeywords(string $metaKeywords): self
    {
        return $this->setData('meta_keywords', $metaKeywords);
    }

    /**
     * Check if page is homepage.
     */
    public function isHomepage(): bool
    {
        $value = $this->getData('is_homepage');

        // PostgreSQL returns booleans as strings ('t'/'f' or '1'/'0')
        return true === $value || 1 === $value || '1' === $value || 't' === $value;
    }

    /**
     * Set homepage flag.
     *
     * @return $this
     */
    public function setIsHomepage(bool $isHomepage): self
    {
        return $this->setData('is_homepage', $isHomepage);
    }
}
