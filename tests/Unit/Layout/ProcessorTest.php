<?php

declare(strict_types=1);

use Infinri\Core\Model\Layout\Processor;

beforeEach(function () {
    $this->processor = new Processor();
});

describe('Processor', function () {
    
    it('can process simple layout', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?><layout><container name="root"/></layout>');
        
        $processed = $this->processor->process($xml);
        
        expect($processed)->toBeInstanceOf(SimpleXMLElement::class);
    });
    
    it('collects named elements', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root"/>
                <block name="test"/>
            </layout>
        ');
        
        $this->processor->process($xml);
        
        $namedElements = $this->processor->getNamedElements();
        
        expect($namedElements)->toHaveKey('root');
        expect($namedElements)->toHaveKey('test');
    });
    
    it('processes remove directives', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <block name="toRemove"/>
                </container>
                <remove name="toRemove"/>
            </layout>
        ');
        
        $processed = $this->processor->process($xml);
        
        // Block should be removed
        $blocks = $processed->xpath('//block[@name="toRemove"]');
        expect($blocks)->toBeEmpty();
        
        // Remove directive should also be removed
        $removes = $processed->xpath('//remove');
        expect($removes)->toBeEmpty();
    });
    
    it('processes referenceContainer directives', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root"/>
                <referenceContainer name="root">
                    <block name="newBlock"/>
                </referenceContainer>
            </layout>
        ');
        
        $processed = $this->processor->process($xml);
        
        // New block should be added to container
        $root = $processed->xpath('//container[@name="root"]');
        expect($root[0]->count())->toBeGreaterThan(0);
        
        // Reference directive should be removed
        $refs = $processed->xpath('//referenceContainer');
        expect($refs)->toBeEmpty();
    });
    
    it('processes referenceBlock directives', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <block name="test"/>
                <referenceBlock name="test">
                    <block name="child"/>
                </referenceBlock>
            </layout>
        ');
        
        $processed = $this->processor->process($xml);
        
        // Child block should be added
        $test = $processed->xpath('//block[@name="test"]');
        expect($test[0]->count())->toBeGreaterThan(0);
    });
    
    it('handles non-existent references gracefully', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <referenceBlock name="nonExistent">
                    <block name="child"/>
                </referenceBlock>
            </layout>
        ');
        
        // Should not throw exception
        $processed = $this->processor->process($xml);
        
        expect($processed)->toBeInstanceOf(SimpleXMLElement::class);
    });
    
    it('collects nested named elements', function () {
        $xml = new SimpleXMLElement('<?xml version="1.0"?>
            <layout>
                <container name="root">
                    <container name="child">
                        <block name="nested"/>
                    </container>
                </container>
            </layout>
        ');
        
        $this->processor->process($xml);
        
        $namedElements = $this->processor->getNamedElements();
        
        expect($namedElements)->toHaveKey('root');
        expect($namedElements)->toHaveKey('child');
        expect($namedElements)->toHaveKey('nested');
    });
    
});
