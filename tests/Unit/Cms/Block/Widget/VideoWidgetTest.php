<?php

use Infinri\Cms\Block\Widget\Video;
use Infinri\Cms\Model\Widget;
use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;

beforeEach(function () {
    $this->widgetResource = Mockery::mock(WidgetResource::class);
    $this->videoWidget = new Video();
});

afterEach(function () {
    Mockery::close();
});

describe('Video Widget Renderer', function () {
    it('renders youtube video', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(1);
        $widget->setWidgetData([
            'video_type' => 'youtube',
            'video_id' => 'dQw4w9WgXcQ',
            'width' => '100%',
            'height' => '400px',
            'autoplay' => false
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toContain('<div class="widget widget-video"');
        expect($html)->toContain('data-widget-id="1"');
        expect($html)->toContain('<iframe');
        expect($html)->toContain('youtube.com/embed/dQw4w9WgXcQ');
        expect($html)->toContain('autoplay=0');
        expect($html)->toContain('width: 100%');
        expect($html)->toContain('height: 400px');
    });
    
    it('renders youtube video with autoplay', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'video_type' => 'youtube',
            'video_id' => 'test123',
            'autoplay' => true
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toContain('autoplay=1');
    });
    
    it('renders vimeo video', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'video_type' => 'vimeo',
            'video_id' => '123456789',
            'autoplay' => false
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toContain('vimeo.com/video/123456789');
        expect($html)->toContain('autoplay=0');
    });
    
    it('renders local video', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'video_type' => 'local',
            'video_id' => '/media/videos/my-video.mp4'
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toContain('src="/media/videos/my-video.mp4"');
    });
    
    it('returns empty string when video_id is missing', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData(['video_type' => 'youtube']);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('returns empty string when video_id is empty', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'video_type' => 'youtube',
            'video_id' => ''
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('uses default dimensions when not specified', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'video_type' => 'youtube',
            'video_id' => 'test'
        ]);
        
        $this->videoWidget->setWidget($widget);
        
        $html = $this->videoWidget->toHtml();
        
        expect($html)->toContain('width: 100%');
        expect($html)->toContain('height: 400px');
    });
});
