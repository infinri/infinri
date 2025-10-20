<?php

use Infinri\Cms\Block\Widget\Image;
use Infinri\Cms\Model\Widget;
use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;

beforeEach(function () {
    $this->widgetResource = Mockery::mock(WidgetResource::class);
    $this->imageWidget = new Image();
});

afterEach(function () {
    Mockery::close();
});

describe('Image Widget Renderer', function () {
    it('renders image without link', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(1);
        $widget->setWidgetData([
            'image_url' => '/media/test.jpg',
            'alt_text' => 'Test Image',
            'css_class' => 'my-image'
        ]);
        
        $this->imageWidget->setWidget($widget);
        
        $html = $this->imageWidget->toHtml();
        
        expect($html)->toContain('<div class="widget widget-image"');
        expect($html)->toContain('data-widget-id="1"');
        expect($html)->toContain('<img');
        expect($html)->toContain('src="/media/test.jpg"');
        expect($html)->toContain('alt="Test Image"');
        expect($html)->toContain('class="widget-image my-image"');
        expect($html)->not->toContain('<a');
    });
    
    it('renders image with link', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(2);
        $widget->setWidgetData([
            'image_url' => '/media/banner.jpg',
            'alt_text' => 'Banner',
            'link_url' => '/products'
        ]);
        
        $this->imageWidget->setWidget($widget);
        
        $html = $this->imageWidget->toHtml();
        
        expect($html)->toContain('<a href="/products"');
        expect($html)->toContain('<img');
        expect($html)->toContain('src="/media/banner.jpg"');
    });
    
    it('renders image with width and height', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([
            'image_url' => '/test.jpg',
            'alt_text' => 'Test',
            'width' => '800',
            'height' => '600'
        ]);
        
        $this->imageWidget->setWidget($widget);
        
        $html = $this->imageWidget->toHtml();
        
        expect($html)->toContain('width="800"');
        expect($html)->toContain('height="600"');
    });
    
    it('returns empty string when image_url is missing', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData(['alt_text' => 'No Image']);
        
        $this->imageWidget->setWidget($widget);
        
        $html = $this->imageWidget->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('returns empty string when image_url is empty', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData(['image_url' => '', 'alt_text' => 'Test']);
        
        $this->imageWidget->setWidget($widget);
        
        $html = $this->imageWidget->toHtml();
        
        expect($html)->toBe('');
    });
});
