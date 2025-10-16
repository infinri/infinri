<?php

declare(strict_types=1);

namespace Tests\Unit\Theme\ViewModel;

use Infinri\Theme\ViewModel\Breadcrumb;
use PHPUnit\Framework\TestCase;

class BreadcrumbTest extends TestCase
{
    private Breadcrumb $viewModel;

    protected function setUp(): void
    {
        $this->viewModel = new Breadcrumb();
    }

    public function test_add_crumb_adds_breadcrumb_item(): void
    {
        $this->viewModel->addCrumb('Home', '/');

        $breadcrumbs = $this->viewModel->getBreadcrumbs();
        
        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['label']);
        $this->assertEquals('/', $breadcrumbs[0]['url']);
    }

    public function test_add_crumb_with_null_url(): void
    {
        $this->viewModel->addCrumb('Current Page', null);

        $breadcrumbs = $this->viewModel->getBreadcrumbs();
        
        $this->assertEquals('Current Page', $breadcrumbs[0]['label']);
        $this->assertNull($breadcrumbs[0]['url']);
    }

    public function test_add_multiple_crumbs(): void
    {
        $this->viewModel->addCrumb('Home', '/');
        $this->viewModel->addCrumb('Category', '/category');
        $this->viewModel->addCrumb('Product', null);

        $breadcrumbs = $this->viewModel->getBreadcrumbs();
        
        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Category', $breadcrumbs[1]['label']);
    }

    public function test_has_breadcrumbs_returns_false_when_empty(): void
    {
        $this->assertFalse($this->viewModel->hasBreadcrumbs());
    }

    public function test_has_breadcrumbs_returns_true_when_not_empty(): void
    {
        $this->viewModel->addCrumb('Home', '/');
        
        $this->assertTrue($this->viewModel->hasBreadcrumbs());
    }

    public function test_get_count_returns_correct_count(): void
    {
        $this->assertEquals(0, $this->viewModel->getCount());

        $this->viewModel->addCrumb('Home', '/');
        $this->viewModel->addCrumb('Products', '/products');

        $this->assertEquals(2, $this->viewModel->getCount());
    }

    public function test_clear_removes_all_breadcrumbs(): void
    {
        $this->viewModel->addCrumb('Home', '/');
        $this->viewModel->addCrumb('Category', '/category');
        
        $this->viewModel->clear();

        $this->assertFalse($this->viewModel->hasBreadcrumbs());
        $this->assertEquals(0, $this->viewModel->getCount());
    }

    public function test_get_structured_data_returns_empty_when_no_breadcrumbs(): void
    {
        $structuredData = $this->viewModel->getStructuredData();
        
        $this->assertEquals('', $structuredData);
    }

    public function test_get_structured_data_returns_valid_json_ld(): void
    {
        $this->viewModel->addCrumb('Home', '/');
        $this->viewModel->addCrumb('Products', '/products');
        $this->viewModel->addCrumb('Item', null);

        $structuredData = $this->viewModel->getStructuredData('https://example.com');
        
        $this->assertNotEmpty($structuredData);
        
        $data = json_decode($structuredData, true);
        
        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertEquals('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        
        $this->assertEquals(1, $data['itemListElement'][0]['position']);
        $this->assertEquals('Home', $data['itemListElement'][0]['name']);
        $this->assertEquals('https://example.com/', $data['itemListElement'][0]['item']);
    }
}
