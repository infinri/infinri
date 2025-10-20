<?php

use Infinri\Cms\Block\Widget\Html;
use Infinri\Cms\Model\Widget;
use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;

beforeEach(function () {
    $this->widgetResource = Mockery::mock(WidgetResource::class);
    $this->htmlWidget = new Html();
});

afterEach(function () {
    Mockery::close();
});

describe('HTML Widget Renderer', function () {
    it('renders html content', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(1);
        $widget->setWidgetData([
            'html_content' => '<div class="test"><h1>Hello World</h1></div>'
        ]);
        
        $this->htmlWidget->setWidget($widget);
        
        $html = $this->htmlWidget->toHtml();
        
        expect($html)->toContain('<div class="widget widget-html"');
        expect($html)->toContain('data-widget-id="1"');
        expect($html)->toContain('<h1>Hello World</h1>');
    });
    
    it('returns empty string when html_content is missing', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData([]);
        
        $this->htmlWidget->setWidget($widget);
        
        $html = $this->htmlWidget->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('returns empty string when html_content is empty', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetData(['html_content' => '']);
        
        $this->htmlWidget->setWidget($widget);
        
        $html = $this->htmlWidget->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('does not escape html content', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(5);
        $widget->setWidgetData([
            'html_content' => '<script>alert("test")</script>'
        ]);
        
        $this->htmlWidget->setWidget($widget);
        
        $html = $this->htmlWidget->toHtml();
        
        // HTML should be preserved as-is (admin-controlled content)
        expect($html)->toContain('<script>alert("test")</script>');
    });
});
