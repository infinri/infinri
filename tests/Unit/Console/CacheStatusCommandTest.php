<?php

declare(strict_types=1);

use Infinri\Core\Console\Command\CacheStatusCommand;
use Infinri\Core\Model\Cache\TypeList;
use Symfony\Component\Console\Tester\CommandTester;

describe('CacheStatusCommand', function () {
    
    beforeEach(function () {
        $this->typeList = new TypeList();
        $this->command = new CacheStatusCommand($this->typeList);
        $this->tester = new CommandTester($this->command);
    });
    
    it('has correct name', function () {
        expect($this->command->getName())->toBe('cache:status');
    });
    
    it('has description', function () {
        expect($this->command->getDescription())->not->toBeEmpty();
    });
    
    it('shows cache status', function () {
        $this->tester->execute([]);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('Cache Status');
    });
    
    it('displays all cache types', function () {
        $this->tester->execute([]);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('config');
        expect($output)->toContain('layout');
        expect($output)->toContain('block_html');
        expect($output)->toContain('full_page');
    });
    
    it('shows enabled/disabled status', function () {
        $this->tester->execute([]);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Enabled');
        expect($output)->toContain('Disabled');
    });
    
    it('shows summary count', function () {
        $this->tester->execute([]);
        
        expect($this->tester->getDisplay())->toContain('Enabled:');
        expect($this->tester->getDisplay())->toContain('cache types');
    });
});
