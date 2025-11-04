<?php

declare(strict_types=1);

namespace Infinri\Cms\Block\Widget;

/**
 * Embeds YouTube, Vimeo, or local videos.
 */
class Video extends AbstractWidget
{
    /**
     * Render video widget.
     */
    public function toHtml(): string
    {
        $data = $this->getWidgetData();

        $videoType = $data['video_type'] ?? 'youtube';
        $videoId = $data['video_id'] ?? '';
        $width = $data['width'] ?? '100%';
        $height = $data['height'] ?? '400px';
        $autoplay = $data['autoplay'] ?? false;

        if (empty($videoId)) {
            return '';
        }

        $embedUrl = $this->getEmbedUrl($videoType, $videoId, $autoplay);

        if (! $embedUrl) {
            return '';
        }

        return \sprintf(
            '<div class="widget widget-video" data-widget-id="%d" data-widget-type="video" style="width: %s; height: %s;">
                <iframe src="%s" 
                        width="100%%" 
                        height="100%%" 
                        frameborder="0" 
                        allowfullscreen
                        alloisDeletedw="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            </div>',
            $this->getWidgetId() ?? 0,
            $this->escapeHtmlAttr($width),
            $this->escapeHtmlAttr($height),
            $this->escapeUrl($embedUrl)
        );
    }

    private function getEmbedUrl(string $type, string $videoId, bool $autoplay): ?string
    {
        return match ($type) {
            'youtube' => \sprintf(
                'https://www.youtube.com/embed/%s?autoplay=%d&rel=0',
                urlencode($videoId),
                $autoplay ? 1 : 0
            ),
            'vimeo' => \sprintf(
                'https://player.vimeo.com/video/%s?autoplay=%d',
                urlencode($videoId),
                $autoplay ? 1 : 0
            ),
            'local' => $videoId, // Local video URL
            default => null
        };
    }
}
