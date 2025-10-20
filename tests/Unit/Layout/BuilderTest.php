<?php

declare(strict_types=1);

use Infinri\Core\Model\Layout\Builder;
use Infinri\Core\Block\Container;
use Infinri\Core\Block\Template;
use Infinri\Core\Block\Text;
use Infinri\Core\Model\View\TemplateResolver;

beforeEach(function () {
    $this->templateResolver = Mockery::mock(TemplateResolver::class);
    $this->builder = new Builder($this->templateResolver);
});

describe('Builder', function () {
    
    it('can build simple container', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="root"/></layout>');
        
        $block = $this->builder->build($xml);
        
        expect($block)->toBeInstanceOf(Container::class);
        expect($block->getName())->toBe('root');
    });
    
    it('can build container with children', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="child"/>
                </container>
            </layout>
        ');
        
        $block = $this->builder->build($xml);
        
        expect($block)->toBeInstanceOf(Container::class);
        expect($block->getChildren())->toHaveCount(1);
    });
    
    it('sets block data from attributes', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="root" htmlTag="section" htmlClass="test"/></layout>');
        
        $block = $this->builder->build($xml);
        
        expect($block->getData('htmlTag'))->toBe('section');
        expect($block->getData('htmlClass'))->toBe('test');
    });
    
    it('can retrieve named blocks', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="header"/>
                    <container name="content"/>
                </container>
            </layout>
        ');
        
        $this->builder->build($xml);
        
        expect($this->builder->getBlock('root'))->not->toBeNull();
        expect($this->builder->getBlock('header'))->not->toBeNull();
        expect($this->builder->getBlock('content'))->not->toBeNull();
    });
    
    it('returns null for non-existent block', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="root"/></layout>');
        
        $this->builder->build($xml);
        
        expect($this->builder->getBlock('nonexistent'))->toBeNull();
    });
    
    it('can build nested structure', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="level1">
                        <container name="level2"/>
                    </container>
                </container>
            </layout>
        ');
        
        $root = $this->builder->build($xml);
        
        expect($root->getName())->toBe('root');
        expect($root->getChildren())->toHaveCount(1);
        
        $level1 = $root->getChild('level1');
        expect($level1)->not->toBeNull();
        expect($level1->getChildren())->toHaveCount(1);
    });
    
    it('returns all built blocks', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="child1"/>
                    <container name="child2"/>
                </container>
            </layout>
        ');
        
        $this->builder->build($xml);
        
        $allBlocks = $this->builder->getAllBlocks();
        
        expect($allBlocks)->toBeArray();
        expect($allBlocks)->toHaveKey('root');
        expect($allBlocks)->toHaveKey('child1');
        expect($allBlocks)->toHaveKey('child2');
    });
    
});
