<?php

declare(strict_types=1);

namespace Tests\Unit\Theme\ViewModel;

use Infinri\Core\Model\Url\Builder as UrlBuilder;
use Infinri\Theme\ViewModel\Pagination;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    private Pagination $viewModel;
    private UrlBuilder $urlBuilder;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->createMock(UrlBuilder::class);
        $this->viewModel = new Pagination($this->urlBuilder);
    }

    public function test_set_pagination_sets_values_correctly(): void
    {
        $this->viewModel->setPagination(2, 100, 20);

        $this->assertEquals(2, $this->viewModel->getCurrentPage());
        $this->assertEquals(100, $this->viewModel->getTotalItems());
        $this->assertEquals(20, $this->viewModel->getPageSize());
        $this->assertEquals(5, $this->viewModel->getTotalPages());
    }

    public function test_set_pagination_enforces_minimum_values(): void
    {
        $this->viewModel->setPagination(-1, -10, 0);

        $this->assertEquals(1, $this->viewModel->getCurrentPage());
        $this->assertEquals(0, $this->viewModel->getTotalItems());
        $this->assertEquals(1, $this->viewModel->getPageSize());
    }

    public function test_total_pages_calculation(): void
    {
        $this->viewModel->setPagination(1, 100, 20);
        $this->assertEquals(5, $this->viewModel->getTotalPages());

        $this->viewModel->setPagination(1, 99, 20);
        $this->assertEquals(5, $this->viewModel->getTotalPages());

        $this->viewModel->setPagination(1, 101, 20);
        $this->assertEquals(6, $this->viewModel->getTotalPages());
    }

    public function test_has_previous_returns_correct_value(): void
    {
        $this->viewModel->setPagination(1, 100, 20);
        $this->assertFalse($this->viewModel->hasPrevious());

        $this->viewModel->setPagination(2, 100, 20);
        $this->assertTrue($this->viewModel->hasPrevious());
    }

    public function test_has_next_returns_correct_value(): void
    {
        $this->viewModel->setPagination(5, 100, 20);
        $this->assertFalse($this->viewModel->hasNext());

        $this->viewModel->setPagination(4, 100, 20);
        $this->assertTrue($this->viewModel->hasNext());
    }

    public function test_get_page_url_with_base_url(): void
    {
        $this->viewModel->setBaseUrl('/products');
        
        $this->assertEquals('/products?page=3', $this->viewModel->getPageUrl(3));
    }

    public function test_get_previous_url_returns_correct_url(): void
    {
        $this->viewModel->setBaseUrl('/items');
        $this->viewModel->setPagination(3, 100, 20);

        $this->assertEquals('/items?page=2', $this->viewModel->getPreviousUrl());
    }

    public function test_get_previous_url_returns_null_on_first_page(): void
    {
        $this->viewModel->setPagination(1, 100, 20);

        $this->assertNull($this->viewModel->getPreviousUrl());
    }

    public function test_get_next_url_returns_correct_url(): void
    {
        $this->viewModel->setBaseUrl('/items');
        $this->viewModel->setPagination(2, 100, 20);

        $this->assertEquals('/items?page=3', $this->viewModel->getNextUrl());
    }

    public function test_get_next_url_returns_null_on_last_page(): void
    {
        $this->viewModel->setPagination(5, 100, 20);

        $this->assertNull($this->viewModel->getNextUrl());
    }

    public function test_get_pages_returns_correct_range(): void
    {
        $this->viewModel->setPagination(5, 200, 20);
        
        $pages = $this->viewModel->getPages(2);
        
        $this->assertEquals([3, 4, 5, 6, 7], $pages);
    }

    public function test_get_pages_respects_boundaries(): void
    {
        $this->viewModel->setPagination(2, 100, 20);
        
        $pages = $this->viewModel->getPages(2);
        
        $this->assertEquals([1, 2, 3, 4], $pages);
    }

    public function test_should_show_first_returns_correct_value(): void
    {
        $this->viewModel->setPagination(5, 200, 20);
        $this->assertTrue($this->viewModel->shouldShowFirst(2));

        $this->viewModel->setPagination(2, 200, 20);
        $this->assertFalse($this->viewModel->shouldShowFirst(2));
    }

    public function test_should_show_last_returns_correct_value(): void
    {
        $this->viewModel->setPagination(3, 200, 20);
        $this->assertTrue($this->viewModel->shouldShowLast(2));

        $this->viewModel->setPagination(9, 200, 20);
        $this->assertFalse($this->viewModel->shouldShowLast(2));
    }
}
