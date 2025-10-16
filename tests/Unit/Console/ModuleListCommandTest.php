<?php

declare(strict_types=1);

use Infinri\Core\Console\Command\ModuleListCommand;
use Symfony\Component\Console\Tester\CommandTester;

describe('ModuleListCommand', function () {
    
    beforeEach(function () {
        // Ensure modules are registered
        require_once dirname(__DIR__, 3) . '/app/autoload.php';
        $this->command = new ModuleListCommand();
        $this->tester = new CommandTester($this->command);
    });
    
    it('has correct name', function () {
        expect($this->command->getName())->toBe('module:list');
    });
    
    it('has description', function () {
        expect($this->command->getDescription())->not->toBeEmpty();
    });
    
    it('lists all modules', function () {
        $this->tester->execute([]);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('Registered Modules');
    });
    
    it('displays module names', function () {
        $this->tester->execute([]);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Infinri_Core');
    });
    
    it('shows module status', function () {
        $this->tester->execute([]);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Status');
        expect($output)->toContain('Enabled');
    });
    
    it('shows summary count', function () {
        $this->tester->execute([]);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Total modules:');
        expect($output)->toContain('Enabled:');
    });
});
