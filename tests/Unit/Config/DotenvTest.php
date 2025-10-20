<?php

declare(strict_types=1);

use Dotenv\Dotenv;

describe('Dotenv', function () {
    
    it('can load environment variables', function () {
        // Create temporary .env file
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, <<<ENV
TEST_VAR=test_value
TEST_NUMBER=123
TEST_BOOL=true
ENV
        );
        
        // Load with phpdotenv (createMutable for test isolation)
        $dotenv = Dotenv::createMutable($tempDir);
        $dotenv->load();
        
        // Verify variables loaded (phpdotenv sets $_ENV and $_SERVER)
        expect($_ENV['TEST_VAR'])->toBe('test_value');
        expect($_SERVER['TEST_VAR'])->toBe('test_value');
        
        // Cleanup
        unlink($envFile);
        rmdir($tempDir);
    });
    
    it('supports variable expansion', function () {
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, <<<'ENV'
BASE_PATH=/var/www
FULL_PATH="${BASE_PATH}/app"
ENV
        );
        
        $dotenv = Dotenv::createMutable($tempDir);
        $dotenv->load();
        
        expect($_ENV['BASE_PATH'])->toBe('/var/www');
        expect($_ENV['FULL_PATH'])->toBe('/var/www/app');
        
        // Cleanup
        unlink($envFile);
        rmdir($tempDir);
    });
    
    it('handles quoted values correctly', function () {
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, <<<'ENV'
SINGLE_QUOTED='single value'
DOUBLE_QUOTED="double value"
WITH_SPACES="  value with spaces  "
ESCAPED="He said \"hello\""
ENV
        );
        
        $dotenv = Dotenv::createMutable($tempDir);
        $dotenv->load();
        
        expect($_ENV['SINGLE_QUOTED'])->toBe('single value');
        expect($_ENV['DOUBLE_QUOTED'])->toBe('double value');
        expect($_ENV['WITH_SPACES'])->toBe('  value with spaces  ');
        expect($_ENV['ESCAPED'])->toBe('He said "hello"');
        
        // Cleanup
        unlink($envFile);
        rmdir($tempDir);
    });
    
    it('ignores comments', function () {
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, <<<ENV
# This is a comment
TEST_VAR=value
# Another comment
ENV
        );
        
        $dotenv = Dotenv::createMutable($tempDir);
        $dotenv->load();
        
        expect($_ENV['TEST_VAR'])->toBe('value');
        
        // Cleanup
        unlink($envFile);
        rmdir($tempDir);
    });
    
    it('does not overwrite existing environment variables when using createImmutable', function () {
        // Set environment variable before loading
        $_ENV['IMMUTABLE_TEST_VAR'] = 'original';
        $_SERVER['IMMUTABLE_TEST_VAR'] = 'original';
        
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, <<<ENV
IMMUTABLE_TEST_VAR=from_file
ENV
        );
        
        // createImmutable won't overwrite existing vars
        $dotenv = Dotenv::createImmutable($tempDir);
        $dotenv->safeLoad();
        
        // Should still have original value
        expect($_ENV['IMMUTABLE_TEST_VAR'])->toBe('original');
        
        // Cleanup
        unset($_ENV['IMMUTABLE_TEST_VAR']);
        unset($_SERVER['IMMUTABLE_TEST_VAR']);
        unlink($envFile);
        rmdir($tempDir);
    });
    
    it('loads empty .env file without errors', function () {
        $tempDir = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        mkdir($tempDir);
        
        // Create empty .env file
        $envFile = $tempDir . '/.env';
        file_put_contents($envFile, '');
        
        // Should load successfully with empty file
        $dotenv = Dotenv::createMutable($tempDir);
        $result = $dotenv->safeLoad();
        
        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
        
        // Cleanup
        unlink($envFile);
        rmdir($tempDir);
    });
    
});
