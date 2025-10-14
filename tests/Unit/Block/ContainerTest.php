<?php

declare(strict_types=1);

use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;

describe('Container', function () {
    
    it('renders empty container with div tag', function () {
        $container = new Container();
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div></div>');
    });
    
    it('renders with custom HTML tag', function () {
        $container = new Container();
        $container->setData('htmlTag', 'section');
        
        $html = $container->toHtml();
        
        expect($html)->toContain('<section>');
        expect($html)->toContain('</section>');
    });
    
    it('renders with HTML class', function () {
        $container = new Container();
        $container->setData('htmlClass', 'test-class');
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div class="test-class"></div>');
    });
    
    it('renders with HTML ID', function () {
        $container = new Container();
        $container->setData('htmlId', 'test-id');
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div id="test-id"></div>');
    });
    
    it('renders with both class and ID', function () {
        $container = new Container();
        $container->setData('htmlId', 'test-id');
        $container->setData('htmlClass', 'test-class');
        
        $html = $container->toHtml();
        
        expect($html)->toContain('id="test-id"');
        expect($html)->toContain('class="test-class"');
    });
    
    it('renders children HTML', function () {
        $container = new Container();
        $child = new Text();
        $child->setText('Hello World');
        
        $container->addChild($child);
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div>Hello World</div>');
    });
    
    it('renders multiple children', function () {
        $container = new Container();
        $child1 = new Text();
        $child1->setText('First');
        $child2 = new Text();
        $child2->setText('Second');
        
        $container->addChild($child1);
        $container->addChild($child2);
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div>FirstSecond</div>');
    });
    
    it('renders nested containers', function () {
        $outer = new Container();
        $outer->setData('htmlClass', 'outer');
        
        $inner = new Container();
        $inner->setData('htmlClass', 'inner');
        
        $text = new Text();
        $text->setText('Content');
        
        $inner->addChild($text);
        $outer->addChild($inner);
        
        $html = $outer->toHtml();
        
        expect($html)->toBe('<div class="outer"><div class="inner">Content</div></div>');
    });
    
});
