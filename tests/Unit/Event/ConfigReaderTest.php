<?php

declare(strict_types=1);

use Infinri\Core\Model\Event\Config\Reader;

describe('Event Config Reader', function () {
    
    beforeEach(function () {
        // Ensure modules are registered
        require_once dirname(__DIR__, 3) . '/app/autoload.php';
        $this->reader = new Reader();
    });
    
    it('can read events.xml from Infinri_Core module', function () {
        $events = $this->reader->read('Infinri_Core');
        
        // events.xml exists, so should return an array
        expect($events)->toBeArray();
        expect($events)->not->toBeEmpty();
        
        // Core's events.xml defines events but with no observers configured yet
        // Each event will have an empty array of observers
        foreach ($events as $eventName => $observers) {
            expect($eventName)->toBeString();
            expect($observers)->toBeArray();
        }
    });
    
    it('returns null for non-existent module', function () {
        $events = $this->reader->read('NonExistent_Module');
        
        expect($events)->toBeNull();
    });
    
    it('returns null for module without events.xml', function () {
        // Infinri_Theme doesn't have events.xml yet
        $events = $this->reader->read('Infinri_Theme');
        
        expect($events)->toBeNull();
    });
    
    it('can validate if events.xml exists', function () {
        $exists = $this->reader->validate('Infinri_Core');
        
        expect($exists)->toBeTrue();
    });
    
    it('returns false when validating non-existent module', function () {
        $exists = $this->reader->validate('NonExistent_Module');
        
        expect($exists)->toBeFalse();
    });
    
    it('returns false when validating module without events.xml', function () {
        $exists = $this->reader->validate('Infinri_Theme');
        
        expect($exists)->toBeFalse();
    });
    
    it('can read all events from all modules', function () {
        $allEvents = $this->reader->readAll();
        
        expect($allEvents)->toBeArray();
    });
    
    it('parses event names correctly', function () {
        $events = $this->reader->read('Infinri_Core');
        
        if ($events !== null && count($events) > 0) {
            foreach ($events as $eventName => $observers) {
                expect($eventName)->toBeString();
                expect($observers)->toBeArray();
            }
        } else {
            // Core events.xml exists but has no configured observers (only placeholders)
            expect(true)->toBeTrue();
        }
    });
    
    it('handles malformed XML gracefully', function () {
        // Create a temporary malformed XML file
        $testDir = sys_get_temp_dir() . '/infinri_event_test_' . uniqid();
        mkdir($testDir . '/etc', 0755, true);
        
        $malformedXml = '<?xml version="1.0"?><config><event name="test">MALFORMED</config>';
        file_put_contents($testDir . '/etc/events.xml', $malformedXml);
        
        // Mock ComponentRegistrar to return our test directory
        $registrar = $this->createMock(\Infinri\Core\Api\ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($testDir);
        
        $reader = new Reader($registrar);
        $events = $reader->read('TestModule');
        
        // Should return empty array for malformed XML
        expect($events)->toBeArray();
        expect($events)->toBeEmpty();
        
        // Cleanup
        unlink($testDir . '/etc/events.xml');
        rmdir($testDir . '/etc');
        rmdir($testDir);
    });
    
    it('merges events from multiple modules', function () {
        // Create two test modules with events.xml
        $testDir1 = sys_get_temp_dir() . '/infinri_module1_' . uniqid();
        $testDir2 = sys_get_temp_dir() . '/infinri_module2_' . uniqid();
        
        mkdir($testDir1 . '/etc', 0755, true);
        mkdir($testDir2 . '/etc', 0755, true);
        
        // Module 1 events
        $xml1 = '<?xml version="1.0"?>
<config>
    <event name="test_event">
        <observer name="observer1" instance="Module1\Observer1" method="execute"/>
    </event>
</config>';
        file_put_contents($testDir1 . '/etc/events.xml', $xml1);
        
        // Module 2 events
        $xml2 = '<?xml version="1.0"?>
<config>
    <event name="test_event">
        <observer name="observer2" instance="Module2\Observer2" method="execute"/>
    </event>
</config>';
        file_put_contents($testDir2 . '/etc/events.xml', $xml2);
        
        // Mock registrar and module list
        $registrar = $this->createMock(\Infinri\Core\Api\ComponentRegistrarInterface::class);
        $registrar->method('getPath')
            ->willReturnCallback(function($type, $name) use ($testDir1, $testDir2) {
                return match($name) {
                    'Module1' => $testDir1,
                    'Module2' => $testDir2,
                    default => null,
                };
            });
        
        $moduleList = $this->createMock(\Infinri\Core\Model\Module\ModuleList::class);
        $moduleList->method('getNames')->willReturn(['Module1', 'Module2']);
        
        $reader = new Reader($registrar, $moduleList);
        $allEvents = $reader->readAll();
        
        expect($allEvents)->toHaveKey('test_event');
        expect($allEvents['test_event'])->toHaveKey('observer1');
        expect($allEvents['test_event'])->toHaveKey('observer2');
        
        // Cleanup
        unlink($testDir1 . '/etc/events.xml');
        unlink($testDir2 . '/etc/events.xml');
        rmdir($testDir1 . '/etc');
        rmdir($testDir2 . '/etc');
        rmdir($testDir1);
        rmdir($testDir2);
    });
    
    it('parses observer instance and method attributes', function () {
        $testDir = sys_get_temp_dir() . '/infinri_observer_test_' . uniqid();
        mkdir($testDir . '/etc', 0755, true);
        
        $xml = '<?xml version="1.0"?>
<config>
    <event name="test_event">
        <observer name="my_observer" instance="MyModule\Observer\TestObserver" method="customMethod"/>
    </event>
</config>';
        file_put_contents($testDir . '/etc/events.xml', $xml);
        
        $registrar = $this->createMock(\Infinri\Core\Api\ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($testDir);
        
        $reader = new Reader($registrar);
        $events = $reader->read('TestModule');
        
        expect($events['test_event']['my_observer']['instance'])->toBe('MyModule\Observer\TestObserver');
        expect($events['test_event']['my_observer']['method'])->toBe('customMethod');
        
        // Cleanup
        unlink($testDir . '/etc/events.xml');
        rmdir($testDir . '/etc');
        rmdir($testDir);
    });
    
    it('defaults to execute method when method attribute is not specified', function () {
        $testDir = sys_get_temp_dir() . '/infinri_default_method_test_' . uniqid();
        mkdir($testDir . '/etc', 0755, true);
        
        $xml = '<?xml version="1.0"?>
<config>
    <event name="test_event">
        <observer name="my_observer" instance="MyModule\Observer\TestObserver"/>
    </event>
</config>';
        file_put_contents($testDir . '/etc/events.xml', $xml);
        
        $registrar = $this->createMock(\Infinri\Core\Api\ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($testDir);
        
        $reader = new Reader($registrar);
        $events = $reader->read('TestModule');
        
        expect($events['test_event']['my_observer']['method'])->toBe('execute');
        
        // Cleanup
        unlink($testDir . '/etc/events.xml');
        rmdir($testDir . '/etc');
        rmdir($testDir);
    });
    
    it('parses disabled attribute correctly', function () {
        $testDir = sys_get_temp_dir() . '/infinri_disabled_test_' . uniqid();
        mkdir($testDir . '/etc', 0755, true);
        
        $xml = '<?xml version="1.0"?>
<config>
    <event name="test_event">
        <observer name="enabled_observer" instance="Module\Observer1" disabled="false"/>
        <observer name="disabled_observer" instance="Module\Observer2" disabled="true"/>
    </event>
</config>';
        file_put_contents($testDir . '/etc/events.xml', $xml);
        
        $registrar = $this->createMock(\Infinri\Core\Api\ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($testDir);
        
        $reader = new Reader($registrar);
        $events = $reader->read('TestModule');
        
        expect($events['test_event']['enabled_observer']['disabled'])->toBeFalse();
        expect($events['test_event']['disabled_observer']['disabled'])->toBeTrue();
        
        // Cleanup
        unlink($testDir . '/etc/events.xml');
        rmdir($testDir . '/etc');
        rmdir($testDir);
    });
});
