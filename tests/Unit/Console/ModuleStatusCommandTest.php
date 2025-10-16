<?php

declare(strict_types=1);

use Infinri\Core\Console\Command\ModuleStatusCommand;
use Symfony\Component\Console\Tester\CommandTester;

describe('ModuleStatusCommand', function () {
    
    beforeEach(function () {
        // Ensure modules are registered
        require_once dirname(__DIR__, 3) . '/app/autoload.php';
        $this->command = new ModuleStatusCommand();
        $this->tester = new CommandTester($this->command);
    });
    
    it('has correct name', function () {
        expect($this->command->getName())->toBe('module:status');
    });
    
    it('has description', function () {
        expect($this->command->getDescription())->not->toBeEmpty();
    });
    
    it('shows module status', function () {
        $this->tester->execute(['module' => 'Infinri_Core']);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('Infinri_Core');
    });
    
    it('displays module details', function () {
        $this->tester->execute(['module' => 'Infinri_Core']);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Version');
        expect($output)->toContain('Status');
    });
    
    it('fails for non-existent module', function () {
        $this->tester->execute(['module' => 'NonExistent_Module']);
        
        expect($this->tester->getStatusCode())->toBe(1);
        expect($this->tester->getDisplay())->toContain('not found');
    });
    
    it('shows dependencies section', function () {
        $this->tester->execute(['module' => 'Infinri_Theme']);
        
        $output = $this->tester->getDisplay();
        
        expect($output)->toContain('Dependencies');
    });
});
