<?php
declare(strict_types=1);

namespace Infinri\Core\Block;

/**
 * Renders a proper <link> tag for CSS files
 * More secure and maintainable than raw HTML in layout XML
 */
class Css extends AbstractBlock
{
    /**
     * @var string CSS file path
     */
    private string $href = '';

    /**
     * @var string Media attribute
     */
    private string $media = 'all';

    /**
     * @var string Rel attribute
     */
    private string $rel = 'stylesheet';

    /**
     * Set CSS href
     *
     * @param string $href
     * @return $this
     */
    public function setHref(string $href): self
    {
        $this->href = $href;
        return $this;
    }

    /**
     * Get CSS href
     *
     * @return string
     */
    public function getHref(): string
    {
        // Check data array first (from XML)
        $dataHref = $this->getData('href');
        if ($dataHref !== null) {
            return $dataHref;
        }

        return $this->href;
    }

    /**
     * Set media attribute
     *
     * @param string $media
     * @return $this
     */
    public function setMedia(string $media): self
    {
        $this->media = $media;
        return $this;
    }

    /**
     * Get media attribute
     *
     * @return string
     */
    public function getMedia(): string
    {
        $dataMedia = $this->getData('media');
        return $dataMedia ?? $this->media;
    }

    /**
     * Render CSS link tag
     *
     * @return string
     */
    public function toHtml(): string
    {
        $href = $this->getHref();

        if (empty($href)) {
            return '';
        }

        $attributes = [
            'rel' => $this->rel,
            'href' => htmlspecialchars($href, ENT_QUOTES, 'UTF-8'),
            'media' => htmlspecialchars($this->getMedia(), ENT_QUOTES, 'UTF-8'),
        ];

        $attributeString = [];
        foreach ($attributes as $key => $value) {
            $attributeString[] = sprintf('%s="%s"', $key, $value);
        }

        return sprintf('<link %s>', implode(' ', $attributeString));
    }
}
