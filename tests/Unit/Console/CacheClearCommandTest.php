<?php

declare(strict_types=1);

use Infinri\Core\Console\Command\CacheClearCommand;
use Infinri\Core\Model\Cache\TypeList;
use Symfony\Component\Console\Tester\CommandTester;

describe('CacheClearCommand', function () {
    
    beforeEach(function () {
        $this->typeList = new TypeList();
        $this->command = new CacheClearCommand($this->typeList);
        $this->tester = new CommandTester($this->command);
    });
    
    it('has correct name', function () {
        expect($this->command->getName())->toBe('cache:clear');
    });
    
    it('has description', function () {
        expect($this->command->getDescription())->not->toBeEmpty();
    });
    
    it('can clear all cache', function () {
        $this->tester->execute([]);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('All cache cleared successfully');
    });
    
    it('can clear specific cache type', function () {
        $this->tester->execute(['--type' => 'config']);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('config');
        expect($this->tester->getDisplay())->toContain('cleared successfully');
    });
    
    it('fails for invalid cache type', function () {
        $this->tester->execute(['--type' => 'invalid_type']);
        
        expect($this->tester->getStatusCode())->toBe(1);
        expect($this->tester->getDisplay())->toContain('does not exist');
    });
    
    it('shows available types on error', function () {
        $this->tester->execute(['--type' => 'invalid']);
        
        expect($this->tester->getDisplay())->toContain('Available types');
    });
    
    it('accepts short option for type', function () {
        $this->tester->execute(['-t' => 'layout']);
        
        expect($this->tester->getStatusCode())->toBe(0);
        expect($this->tester->getDisplay())->toContain('layout');
    });
});
