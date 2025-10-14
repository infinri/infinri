<?php

declare(strict_types=1);

use Infinri\Core\Model\Layout\Builder;
use Infinri\Core\Model\Layout\Renderer;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Text;

beforeEach(function () {
    // Create builder and renderer without ObjectManager for simple tests
    $this->builder = new Builder();
    $this->renderer = new Renderer();
});

describe('Renderer', function () {
    
    it('can render simple container', function () {
        $container = new Container();
        $container->setData('htmlTag', 'div');
        
        $html = $this->renderer->render($container);
        
        expect($html)->toBe('<div></div>');
    });
    
    it('can render container with children', function () {
        $container = new Container();
        $text = new Text();
        $text->setText('Hello');
        
        $container->addChild($text);
        
        $html = $this->renderer->render($container);
        
        expect($html)->toBe('<div>Hello</div>');
    });
    
    it('can render complex nested structure', function () {
        $root = new Container();
        $root->setData('htmlClass', 'root');
        
        $child1 = new Container();
        $child1->setData('htmlClass', 'child');
        
        $text = new Text();
        $text->setText('Content');
        
        $child1->addChild($text);
        $root->addChild($child1);
        
        $html = $this->renderer->render($root);
        
        expect($html)->toBe('<div class="root"><div class="child">Content</div></div>');
    });
    
    it('can render specific block by name', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="target" htmlClass="target-class"/>
                </container>
            </layout>
        ');
        
        $this->builder->build($xml);
        
        $html = $this->renderer->renderBlock($this->builder, 'target');
        
        expect($html)->toBe('<div class="target-class"></div>');
    });
    
    it('returns empty string for non-existent block', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="root"/></layout>');
        
        $this->builder->build($xml);
        
        $html = $this->renderer->renderBlock($this->builder, 'nonexistent');
        
        expect($html)->toBe('');
    });
    
    it('renders full layout from XML', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root" htmlTag="html">
                    <container name="body" htmlTag="body">
                        <container name="content" htmlClass="content"/>
                    </container>
                </container>
            </layout>
        ');
        
        $root = $this->builder->build($xml);
        $html = $this->renderer->render($root);
        
        expect($html)->toContain('<html>');
        expect($html)->toContain('<body>');
        expect($html)->toContain('<div class="content">');
    });
    
});
