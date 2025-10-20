<?php
declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

/**
 * Renders images with optional links
 */
class Image extends AbstractWidget
{
    /**
     * Render image widget
     *
     * @return string
     */
    public function toHtml(): string
    {
        $data = $this->getWidgetData();
        
        $imageUrl = $data['image_url'] ?? '';
        $altText = $data['alt_text'] ?? '';
        $linkUrl = $data['link_url'] ?? null;
        $cssClass = $data['css_class'] ?? '';
        $width = $data['width'] ?? null;
        $height = $data['height'] ?? null;
        
        if (empty($imageUrl)) {
            return '';
        }
        
        // Build image tag
        $imgAttributes = [
            sprintf('src="%s"', $this->escapeUrl($imageUrl)),
            sprintf('alt="%s"', $this->escapeHtmlAttr($altText)),
            sprintf('class="widget-image %s"', $this->escapeHtmlAttr($cssClass)),
        ];
        
        if ($width) {
            $imgAttributes[] = sprintf('width="%s"', $this->escapeHtmlAttr((string)$width));
        }
        
        if ($height) {
            $imgAttributes[] = sprintf('height="%s"', $this->escapeHtmlAttr((string)$height));
        }
        
        $img = sprintf('<img %s>', implode(' ', $imgAttributes));

        // Wrap in link if specified
        if ($linkUrl) {
            $img = sprintf(
                '<a href="%s" class="widget-image-link">%s</a>',
                $this->escapeUrl($linkUrl),
                $img
            );
        }
        
        return sprintf(
            '<div class="widget widget-image" data-widget-id="%d" data-widget-type="image">%s</div>',
            $this->getWidgetId() ?? 0,
            $img
        );
    }
}
