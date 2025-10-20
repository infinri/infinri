<?php

use Infinri\Cms\Model\Page;
use Infinri\Cms\Model\ResourceModel\Page as PageResource;

beforeEach(function () {
    // Create a mock resource model
    $this->resourceMock = Mockery::mock(PageResource::class);
    $this->resourceMock->shouldReceive('getIdFieldName')->andReturn('page_id');
    $this->page = new Page($this->resourceMock);
});

afterEach(function () {
    Mockery::close();
});

test('can create page instance', function () {
    expect($this->page)->toBeInstanceOf(Page::class);
});

test('can set and get page data', function () {
    $this->page->setData('title', 'About Us');
    expect($this->page->getData('title'))->toBe('About Us');
});

test('can set data as array', function () {
    $data = [
        'title' => 'New Page',
        'url_key' => 'new-page',
        'content' => '<p>Content</p>',
        'is_active' => true,
    ];
    
    $this->page->setData($data);
    
    expect($this->page->getData('title'))->toBe('New Page');
    expect($this->page->getData('url_key'))->toBe('new-page');
    expect($this->page->getData('content'))->toBe('<p>Content</p>');
    expect($this->page->getData('is_active'))->toBe(true);
});

test('can get all data as array', function () {
    $this->page->setData('title', 'Test');
    $this->page->setData('url_key', 'test');
    
    $data = $this->page->getData();
    
    expect($data)->toBeArray();
    expect($data)->toHaveKey('title');
    expect($data['title'])->toBe('Test');
});

test('has getId method', function () {
    $this->page->setData('page_id', 5);
    expect($this->page->getId())->toBe(5);
});

test('has getTitle method', function () {
    $this->page->setData('title', 'My Title');
    expect($this->page->getTitle())->toBe('My Title');
});

test('has getContent method', function () {
    $this->page->setData('content', '<h1>Content</h1>');
    expect($this->page->getContent())->toBe('<h1>Content</h1>');
});

test('has getUrlKey method', function () {
    $this->page->setData('url_key', 'my-page');
    expect($this->page->getUrlKey())->toBe('my-page');
});

test('has isActive method', function () {
    $this->page->setData('is_active', true);
    expect($this->page->isActive())->toBe(true);
    
    $this->page->setData('is_active', false);
    expect($this->page->isActive())->toBe(false);
});

test('has getMetaTitle method', function () {
    $this->page->setData('meta_title', 'SEO Title');
    expect($this->page->getMetaTitle())->toBe('SEO Title');
});

test('has getMetaDescription method', function () {
    $this->page->setData('meta_description', 'SEO Description');
    expect($this->page->getMetaDescription())->toBe('SEO Description');
});

test('can create page with initial data', function () {
    $data = ['title' => 'Initial', 'url_key' => 'initial'];
    $page = new Page($this->resourceMock, $data);
    
    expect($page->getData('title'))->toBe('Initial');
    expect($page->getData('url_key'))->toBe('initial');
});
