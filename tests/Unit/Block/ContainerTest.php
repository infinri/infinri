<?php

declare(strict_types=1);

use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;

describe('Container', function () {
    
    it('renders children without wrapper when htmlTag not set', function () {
        $container = new Container();
        
        $text = new Text();
        $text->setText('Hello World');
        
        $container->addChild($text);
        
        $html = $container->toHtml();
        
        // No wrapper div when htmlTag is not set
        expect($html)->toBe('Hello World');
    });
    
    it('renders with HTML tag when htmlTag is set', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        
        $text = new Text();
        $text->setText('Content');
        
        $container->addChild($text);
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div>Content</div>');
    });
    
    it('renders with HTML tag and class', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        $container->setData('htmlClass', 'my-class');
        
        $text = new Text();
        $text->setText('Content');
        
        $container->addChild($text);
        
        $html = $container->toHtml();
        
        expect($html)->toContain('class="my-class"');
        expect($html)->toContain('Content');
    });
    
    it('renders with HTML tag and ID', function () {
        $container = new Container();
        $container->setData('htmlTag', 'section');
        $container->setData('htmlId', 'main');
        
        $text = new Text();
        $text->setText('Main Content');
        
        $container->addChild($text);
        
        $html = $container->toHtml();
        
        expect($html)->toContain('<section');
        expect($html)->toContain('id="main"');
        expect($html)->toContain('Main Content');
    });
    
    it('renders with both class and ID', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        $container->setData('htmlClass', 'wrapper');
        $container->setData('htmlId', 'content');
        
        $html = $container->toHtml();
        
        expect($html)->toContain('class="wrapper"');
        expect($html)->toContain('id="content"');
    });
    
    it('renders children in order', function () {
        $container = new Container();
        // No htmlTag - renders children only
        
        $text1 = new Text();
        $text1->setText('First');
        
        $text2 = new Text();
        $text2->setText('Second');
        
        $container->addChild($text1);
        $container->addChild($text2);
        
        $html = $container->toHtml();
        
        expect($html)->toBe('FirstSecond');
    });
    
    it('renders multiple children with wrapper', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        
        $text1 = new Text();
        $text1->setText('One');
        
        $text2 = new Text();
        $text2->setText('Two');
        
        $container->addChild($text1);
        $container->addChild($text2);
        
        $html = $container->toHtml();
        
        expect($html)->toBe('<div>OneTwo</div>');
    });
    
    it('renders nested containers', function () {
        $parent = new Container();
        $parent->setData('htmlTag', 'div');
        $parent->setData('htmlClass', 'parent');
        
        $child = new Container();
        $child->setData('htmlTag', 'div');
        $child->setData('htmlClass', 'child');
        
        $text = new Text();
        $text->setText('Nested');
        
        $child->addChild($text);
        $parent->addChild($child);
        
        $html = $parent->toHtml();
        
        expect($html)->toContain('class="parent"');
        expect($html)->toContain('class="child"');
        expect($html)->toContain('Nested');
    });
    
    it('renders empty container with htmlTag', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        
        $html = $container->toHtml();
        
        // Empty container with htmlTag renders as empty paired tags
        expect($html)->toBe('<div></div>');
    });
    
    it('renders empty container without htmlTag as empty string', function () {
        $container = new Container();
        
        $html = $container->toHtml();
        
        // Empty when no htmlTag and no children
        expect($html)->toBe('');
    });
});
