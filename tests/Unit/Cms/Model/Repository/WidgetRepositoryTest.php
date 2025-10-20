<?php

use Infinri\Cms\Model\Widget;
use Infinri\Cms\Model\ResourceModel\Widget as WidgetResource;
use Infinri\Cms\Model\Repository\WidgetRepository;

beforeEach(function () {
    $this->pdo = Mockery::mock(PDO::class);
    $this->widgetResource = Mockery::mock(WidgetResource::class);
    $this->repository = new WidgetRepository($this->pdo, $this->widgetResource);
});

afterEach(function () {
    Mockery::close();
});

describe('WidgetRepository', function () {
    it('gets widget by id', function () {
        $widgetData = [
            'widget_id' => 1,
            'page_id' => 5,
            'widget_type' => Widget::TYPE_HTML,
            'widget_data' => json_encode(['html_content' => '<h1>Test</h1>']),
            'sort_order' => 10,
            'is_active' => 1,
        ];
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->once()
            ->with(['widget_id' => 1]);
        $stmt->shouldReceive('fetch')
            ->once()
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($widgetData);
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*FROM cms_page_widget.*WHERE widget_id/'))
            ->andReturn($stmt);
        
        $widget = $this->repository->getById(1);
        
        expect($widget)->toBeInstanceOf(Widget::class);
        expect($widget->getWidgetId())->toBe(1);
        expect($widget->getPageId())->toBe(5);
    });
    
    it('throws exception when widget not found', function () {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')->once();
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        
        $this->pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        
        $this->repository->getById(999);
    })->throws(RuntimeException::class, 'Widget with ID 999 not found');
    
    it('gets widgets by page id (active only)', function () {
        $widgetData1 = [
            'widget_id' => 1,
            'page_id' => 5,
            'widget_type' => Widget::TYPE_HTML,
            'widget_data' => '{}',
            'sort_order' => 1,
            'is_active' => 1,
        ];
        
        $widgetData2 = [
            'widget_id' => 2,
            'page_id' => 5,
            'widget_type' => Widget::TYPE_IMAGE,
            'widget_data' => '{}',
            'sort_order' => 2,
            'is_active' => 1,
        ];
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->once()
            ->with(['page_id' => 5]);
        $stmt->shouldReceive('fetch')
            ->times(3)
            ->with(PDO::FETCH_ASSOC)
            ->andReturn($widgetData1, $widgetData2, false);
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*FROM cms_page_widget.*WHERE page_id.*AND is_active.*ORDER BY sort_order/'))
            ->andReturn($stmt);
        
        $widgets = $this->repository->getByPageId(5, true);
        
        expect($widgets)->toHaveCount(2);
        expect($widgets[0]->getWidgetId())->toBe(1);
        expect($widgets[1]->getWidgetId())->toBe(2);
    });
    
    it('gets widgets by page id (including inactive)', function () {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')->once()->with(['page_id' => 5]);
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*FROM cms_page_widget.*WHERE page_id.*ORDER BY sort_order/'))
            ->andReturn($stmt);
        
        $widgets = $this->repository->getByPageId(5, false);
        
        expect($widgets)->toBeArray();
        expect($widgets)->toHaveCount(0);
    });
    
    it('saves new widget (insert)', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setPageId(5);
        $widget->setWidgetType(Widget::TYPE_HTML);
        $widget->setWidgetData(['html_content' => '<p>Test</p>']);
        $widget->setSortOrder(10);
        $widget->setIsActive(true);
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['page_id'] === 5
                    && $params['widget_type'] === Widget::TYPE_HTML
                    && $params['sort_order'] === 10
                    && $params['is_active'] === 1;
            }));
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/INSERT INTO cms_page_widget/'))
            ->andReturn($stmt);
        
        $this->pdo->shouldReceive('lastInsertId')
            ->once()
            ->andReturn('42');
        
        $savedWidget = $this->repository->save($widget);
        
        expect($savedWidget->getWidgetId())->toBe(42);
    });
    
    it('saves existing widget (update)', function () {
        $widget = new Widget($this->widgetResource);
        $widget->setWidgetId(10);
        $widget->setPageId(5);
        $widget->setWidgetType(Widget::TYPE_HTML);
        $widget->setWidgetData(['html_content' => '<p>Updated</p>']);
        $widget->setSortOrder(15);
        $widget->setIsActive(false);
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($params) {
                return $params['widget_id'] === 10
                    && $params['page_id'] === 5
                    && $params['sort_order'] === 15
                    && $params['is_active'] === 0;
            }));
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/UPDATE cms_page_widget/'))
            ->andReturn($stmt);
        
        $savedWidget = $this->repository->save($widget);
        
        expect($savedWidget->getWidgetId())->toBe(10);
    });
    
    it('validates widget before saving', function () {
        $widget = new Widget($this->widgetResource);
        // Invalid widget - missing required fields
        
        $this->repository->save($widget);
    })->throws(InvalidArgumentException::class);
    
    it('deletes widget by id', function () {
        // First prepare for getById check
        $getStmt = Mockery::mock(PDOStatement::class);
        $getStmt->shouldReceive('execute')->once();
        $getStmt->shouldReceive('fetch')->once()->andReturn([
            'widget_id' => 1,
            'page_id' => 5,
            'widget_type' => Widget::TYPE_HTML,
            'widget_data' => '{}',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        
        // Then prepare for delete
        $deleteStmt = Mockery::mock(PDOStatement::class);
        $deleteStmt->shouldReceive('execute')
            ->once()
            ->with(['widget_id' => 1])
            ->andReturn(true);
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/SELECT.*FROM cms_page_widget/'))
            ->andReturn($getStmt);
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::pattern('/DELETE FROM cms_page_widget/'))
            ->andReturn($deleteStmt);
        
        $result = $this->repository->delete(1);
        
        expect($result)->toBeTrue();
    });
    
    it('throws exception when deleting non-existent widget', function () {
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')->once();
        $stmt->shouldReceive('fetch')->once()->andReturn(false);
        
        $this->pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        
        $this->repository->delete(999);
    })->throws(RuntimeException::class, 'Widget with ID 999 not found');
    
    it('reorders widgets for a page', function () {
        $widgetIds = [15, 12, 18];
        
        $this->pdo->shouldReceive('beginTransaction')->once();
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->times(3)
            ->with(Mockery::type('array'));
        
        $this->pdo->shouldReceive('prepare')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturn($stmt);
        
        $this->pdo->shouldReceive('commit')->once();
        $this->pdo->shouldReceive('rollBack')->zeroOrMoreTimes();
        
        $result = $this->repository->reorder(5, $widgetIds);
        
        expect($result)->toBeTrue();
    });
    
    it('rolls back transaction on reorder failure', function () {
        $widgetIds = [15, 12];
        
        $this->pdo->shouldReceive('beginTransaction')->once();
        
        $stmt = Mockery::mock(PDOStatement::class);
        $stmt->shouldReceive('execute')
            ->once()
            ->andThrow(new Exception('Database error'));
        
        $this->pdo->shouldReceive('prepare')->once()->andReturn($stmt);
        $this->pdo->shouldReceive('rollBack')->once();
        
        $this->repository->reorder(5, $widgetIds);
    })->throws(RuntimeException::class, 'Failed to reorder widgets');
});
