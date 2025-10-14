<?php

declare(strict_types=1);

use Infinri\Core\Block\Text;
use Infinri\Core\Block\Container;

describe('AbstractBlock', function () {
    
    it('can set and get name', function () {
        $block = new Text();
        $block->setName('test_block');
        
        expect($block->getName())->toBe('test_block');
    });
    
    it('can add and get children', function () {
        $parent = new Container();
        $child = new Text();
        $child->setName('child');
        
        $parent->addChild($child);
        
        expect($parent->getChild('child'))->toBe($child);
    });
    
    it('sets parent when adding child', function () {
        $parent = new Container();
        $child = new Text();
        
        $parent->addChild($child);
        
        expect($child->getParent())->toBe($parent);
    });
    
    it('can get all children', function () {
        $parent = new Container();
        $child1 = new Text();
        $child2 = new Text();
        
        $parent->addChild($child1, 'first');
        $parent->addChild($child2, 'second');
        
        $children = $parent->getChildren();
        
        expect($children)->toHaveCount(2);
        expect($children)->toHaveKey('first');
        expect($children)->toHaveKey('second');
    });
    
    it('can remove child', function () {
        $parent = new Container();
        $child = new Text();
        
        $parent->addChild($child, 'test');
        $parent->removeChild('test');
        
        expect($parent->getChild('test'))->toBeNull();
    });
    
    it('can set and get data', function () {
        $block = new Text();
        $block->setData('key', 'value');
        
        expect($block->getData('key'))->toBe('value');
    });
    
    it('returns null for non-existent data', function () {
        $block = new Text();
        
        expect($block->getData('nonexistent'))->toBeNull();
    });
    
    it('can get all data', function () {
        $block = new Text();
        $block->setData('key1', 'value1');
        $block->setData('key2', 'value2');
        
        $data = $block->getData();
        
        expect($data)->toBeArray();
        expect($data)->toHaveKey('key1');
        expect($data)->toHaveKey('key2');
    });
    
});
