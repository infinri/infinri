<?php

declare(strict_types=1);

use Infinri\Core\Console\CommandLoader;
use Symfony\Component\Console\Command\Command;

describe('CommandLoader', function () {
    
    beforeEach(function () {
        // Ensure modules and commands are available
        require_once dirname(__DIR__, 3) . '/app/autoload.php';
        $this->loader = new CommandLoader();
    });
    
    it('can load commands', function () {
        $commands = $this->loader->loadCommands();
        
        expect($commands)->toBeArray();
        expect(count($commands))->toBeGreaterThan(0);
    });
    
    it('loads core commands', function () {
        $commands = $this->loader->loadCommands();
        
        $commandNames = array_map(fn($cmd) => $cmd->getName(), $commands);
        
        expect($commandNames)->toContain('cache:clear');
        expect($commandNames)->toContain('cache:status');
        expect($commandNames)->toContain('module:list');
        expect($commandNames)->toContain('module:status');
    });
    
    it('all loaded commands are Symfony Command instances', function () {
        $commands = $this->loader->loadCommands();
        
        foreach ($commands as $command) {
            expect($command)->toBeInstanceOf(Command::class);
        }
    });
    
    it('can register commands manually', function () {
        $mockCommand = $this->createMock(Command::class);
        $mockCommand->method('getName')->willReturn('test:command');
        
        $this->loader->registerCommand($mockCommand);
        $commands = $this->loader->getCommands();
        
        expect($commands)->toContain($mockCommand);
    });
    
    it('can get loaded commands', function () {
        $this->loader->loadCommands();
        $commands = $this->loader->getCommands();
        
        expect($commands)->toBeArray();
        expect(count($commands))->toBeGreaterThan(0);
    });
    
    it('returns empty array before loading', function () {
        $commands = $this->loader->getCommands();
        
        expect($commands)->toBeArray();
        expect($commands)->toBeEmpty();
    });
});
