<?php

declare(strict_types=1);

use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;
use Infinri\Cms\Model\Widget;

beforeEach(function () {
    $this->widgetResource = Mockery::mock(WidgetResource::class);
    $this->widget = new Widget($this->widgetResource);
});

afterEach(function () {
    Mockery::close();
});

describe('Widget Entity', function () {
    it('can be created with initial data', function () {
        $data = [
            'widget_id' => 1,
            'page_id' => 5,
            'widget_type' => Widget::TYPE_HTML,
            'widget_data' => json_encode(['html_content' => '<h1>Test</h1>']),
            'sort_order' => 10,
            'is_active' => true,
        ];

        $widget = new Widget($this->widgetResource, $data);

        expect($widget->getWidgetId())->toBe(1);
        expect($widget->getPageId())->toBe(5);
        expect($widget->getWidgetType())->toBe(Widget::TYPE_HTML);
        expect($widget->getSortOrder())->toBe(10);
        expect($widget->getIsActive())->toBeTrue();
    });

    it('handles widget data as JSON', function () {
        $data = ['html_content' => '<div>Hello World</div>'];

        $this->widget->setWidgetData($data);

        expect($this->widget->getWidgetData())->toBe($data);
    });

    it('decodes JSON widget data on get', function () {
        $jsonData = json_encode(['image_url' => '/test.jpg', 'alt_text' => 'Test']);

        $widget = new Widget($this->widgetResource, ['widget_data' => $jsonData]);

        $widgetData = $widget->getWidgetData();
        expect($widgetData)->toBeArray();
        expect($widgetData['image_url'])->toBe('/test.jpg');
        expect($widgetData['alt_text'])->toBe('Test');
    });

    it('accepts all valid widget types', function () {
        $validTypes = [Widget::TYPE_HTML, Widget::TYPE_BLOCK, Widget::TYPE_IMAGE, Widget::TYPE_VIDEO];

        foreach ($validTypes as $type) {
            $this->widget->setWidgetType($type);
            expect($this->widget->getWidgetType())->toBe($type);
        }
    });

    it('throws exception for invalid widget type', function () {
        $this->widget->setWidgetType('invalid_type');
    })->throws(InvalidArgumentException::class, 'not registered');

    it('validates required page id', function () {
        $this->widget->setWidgetType(Widget::TYPE_HTML);
        $this->widget->setWidgetData(['test' => 'data']);

        $this->widget->validate();
    })->throws(InvalidArgumentException::class, 'Page ID is required');

    it('validates required widget type', function () {
        $this->widget->setPageId(1);
        $this->widget->setWidgetData(['test' => 'data']);

        $this->widget->validate();
    })->throws(InvalidArgumentException::class, 'Widget type is required');

    it('validates widget type is valid', function () {
        $this->widget->setPageId(1);
        $this->widget->setData('widget_type', 'invalid');
        $this->widget->setWidgetData(['test' => 'data']);

        $this->widget->validate();
    })->throws(InvalidArgumentException::class, 'Invalid widget type');

    it('passes validation with all required fields', function () {
        $this->widget->setPageId(1);
        $this->widget->setWidgetType(Widget::TYPE_HTML);
        $this->widget->setWidgetData(['html_content' => '<p>Test</p>']);

        $this->widget->validate();

        expect(true)->toBeTrue(); // No exception thrown
    });

    it('sets and gets sort order', function () {
        $this->widget->setSortOrder(42);

        expect($this->widget->getSortOrder())->toBe(42);
    });

    it('sets and gets is active flag', function () {
        $this->widget->setIsActive(true);
        expect($this->widget->getIsActive())->toBeTrue();

        $this->widget->setIsActive(false);
        expect($this->widget->getIsActive())->toBeFalse();
    });

    it('returns empty array for invalid widget data JSON', function () {
        $widget = new Widget($this->widgetResource, ['widget_data' => 'invalid json']);

        expect($widget->getWidgetData())->toBe([]);
    });

    it('has correct widget type constants', function () {
        expect(Widget::TYPE_HTML)->toBe('html');
        expect(Widget::TYPE_BLOCK)->toBe('block');
        expect(Widget::TYPE_IMAGE)->toBe('image');
        expect(Widget::TYPE_VIDEO)->toBe('video');

        expect(Widget::VALID_TYPES)->toContain('html');
        expect(Widget::VALID_TYPES)->toContain('block');
        expect(Widget::VALID_TYPES)->toContain('image');
        expect(Widget::VALID_TYPES)->toContain('video');
    });
});
