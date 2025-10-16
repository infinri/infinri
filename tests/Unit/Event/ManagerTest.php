<?php

declare(strict_types=1);

use Infinri\Core\Model\Event\Manager;
use Infinri\Core\Api\ObserverInterface;

describe('Event Manager', function () {
    
    beforeEach(function () {
        $this->manager = new Manager();
        
        // Create mock observers
        $this->observer1 = new class implements ObserverInterface {
            public array $executedData = [];
            public function execute(array $data = []): void {
                $this->executedData[] = $data;
            }
        };
        
        $this->observer2 = new class implements ObserverInterface {
            public array $executedData = [];
            public function execute(array $data = []): void {
                $this->executedData[] = $data;
            }
        };
    });
    
    it('can add an observer to an event', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        
        expect($this->manager->hasObservers('test_event'))->toBeTrue();
    });
    
    it('can dispatch an event to observers', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        
        $this->manager->dispatch('test_event', ['key' => 'value']);
        
        expect($this->observer1->executedData)->toHaveCount(1);
        expect($this->observer1->executedData[0])->toBe(['key' => 'value']);
    });
    
    it('can dispatch event to multiple observers', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        $this->manager->addObserver('test_event', 'observer2', $this->observer2);
        
        $this->manager->dispatch('test_event', ['data' => 'test']);
        
        expect($this->observer1->executedData)->toHaveCount(1);
        expect($this->observer2->executedData)->toHaveCount(1);
    });
    
    it('executes observers in priority order', function () {
        $executionOrder = [];
        
        $observer1 = new class($executionOrder) implements ObserverInterface {
            public function __construct(private array &$order) {}
            public function execute(array $data = []): void {
                $this->order[] = 'observer1';
            }
        };
        
        $observer2 = new class($executionOrder) implements ObserverInterface {
            public function __construct(private array &$order) {}
            public function execute(array $data = []): void {
                $this->order[] = 'observer2';
            }
        };
        
        $observer3 = new class($executionOrder) implements ObserverInterface {
            public function __construct(private array &$order) {}
            public function execute(array $data = []): void {
                $this->order[] = 'observer3';
            }
        };
        
        $this->manager->addObserver('test_event', 'observer1', $observer1, 10); // Medium priority
        $this->manager->addObserver('test_event', 'observer2', $observer2, 20); // High priority
        $this->manager->addObserver('test_event', 'observer3', $observer3, 5);  // Low priority
        
        $this->manager->dispatch('test_event');
        
        // Should execute in priority order: 20, 10, 5
        expect($executionOrder)->toBe(['observer2', 'observer1', 'observer3']);
    });
    
    it('can remove an observer', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        $this->manager->removeObserver('test_event', 'observer1');
        
        expect($this->manager->hasObservers('test_event'))->toBeFalse();
    });
    
    it('can get all observers for an event', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        $this->manager->addObserver('test_event', 'observer2', $this->observer2);
        
        $observers = $this->manager->getObservers('test_event');
        
        expect($observers)->toHaveCount(2);
        expect($observers)->toHaveKey('observer1');
        expect($observers)->toHaveKey('observer2');
    });
    
    it('returns empty array for event with no observers', function () {
        $observers = $this->manager->getObservers('nonexistent_event');
        
        expect($observers)->toBeEmpty();
    });
    
    it('can check if event has observers', function () {
        expect($this->manager->hasObservers('test_event'))->toBeFalse();
        
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        
        expect($this->manager->hasObservers('test_event'))->toBeTrue();
    });
    
    it('can clear all observers', function () {
        $this->manager->addObserver('event1', 'observer1', $this->observer1);
        $this->manager->addObserver('event2', 'observer2', $this->observer2);
        
        $this->manager->clearObservers();
        
        expect($this->manager->hasObservers('event1'))->toBeFalse();
        expect($this->manager->hasObservers('event2'))->toBeFalse();
    });
    
    it('can disable an observer', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        $this->manager->disableObserver('test_event', 'observer1');
        
        $this->manager->dispatch('test_event', ['data' => 'test']);
        
        // Observer should not execute
        expect($this->observer1->executedData)->toBeEmpty();
    });
    
    it('can enable a disabled observer', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1, 0, true); // Start disabled
        $this->manager->enableObserver('test_event', 'observer1');
        
        $this->manager->dispatch('test_event', ['data' => 'test']);
        
        // Observer should execute
        expect($this->observer1->executedData)->toHaveCount(1);
    });
    
    it('does not execute disabled observers', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1, 0, true); // Disabled
        
        $this->manager->dispatch('test_event', ['data' => 'test']);
        
        expect($this->observer1->executedData)->toBeEmpty();
    });
    
    it('can dispatch event with no data', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        
        $this->manager->dispatch('test_event');
        
        expect($this->observer1->executedData)->toHaveCount(1);
        expect($this->observer1->executedData[0])->toBe([]);
    });
    
    it('does nothing when dispatching event with no observers', function () {
        // Should not throw exception
        $this->manager->dispatch('nonexistent_event', ['data' => 'test']);
        
        expect(true)->toBeTrue();
    });
    
    it('can get Symfony EventDispatcher instance', function () {
        $dispatcher = $this->manager->getDispatcher();
        
        expect($dispatcher)->toBeInstanceOf(\Symfony\Component\EventDispatcher\EventDispatcher::class);
    });
    
    it('preserves observer data structure', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1, 10);
        
        $observers = $this->manager->getObservers('test_event');
        
        expect($observers['observer1'])->toHaveKey('instance');
        expect($observers['observer1'])->toHaveKey('priority');
        expect($observers['observer1'])->toHaveKey('disabled');
        expect($observers['observer1']['priority'])->toBe(10);
        expect($observers['observer1']['disabled'])->toBeFalse();
    });
    
    it('can handle multiple dispatches to same event', function () {
        $this->manager->addObserver('test_event', 'observer1', $this->observer1);
        
        $this->manager->dispatch('test_event', ['count' => 1]);
        $this->manager->dispatch('test_event', ['count' => 2]);
        $this->manager->dispatch('test_event', ['count' => 3]);
        
        expect($this->observer1->executedData)->toHaveCount(3);
        expect($this->observer1->executedData[0]['count'])->toBe(1);
        expect($this->observer1->executedData[1]['count'])->toBe(2);
        expect($this->observer1->executedData[2]['count'])->toBe(3);
    });
});
