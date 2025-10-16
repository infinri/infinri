<?php

declare(strict_types=1);

use Infinri\Core\Helper\Translation;

describe('Translation Helper', function () {
    
    beforeEach(function () {
        $this->translation = new Translation();
    });
    
    it('has default locale', function () {
        expect($this->translation->getLocale())->toBe('en_US');
    });
    
    it('can set locale', function () {
        $this->translation->setLocale('fr_FR');
        
        expect($this->translation->getLocale())->toBe('fr_FR');
    });
    
    it('returns original text when no translation', function () {
        $result = $this->translation->translate('Hello World');
        
        expect($result)->toBe('Hello World');
    });
    
    it('can add translations', function () {
        $this->translation->addTranslations('fr_FR', [
            'Hello' => 'Bonjour',
            'World' => 'Monde',
        ]);
        
        expect($this->translation->hasTranslation('Hello', 'fr_FR'))->toBeTrue();
    });
    
    it('translates text for current locale', function () {
        $this->translation->setLocale('fr_FR');
        $this->translation->addTranslations('fr_FR', [
            'Hello' => 'Bonjour',
        ]);
        
        $result = $this->translation->translate('Hello');
        
        expect($result)->toBe('Bonjour');
    });
    
    it('supports sprintf formatting', function () {
        $this->translation->setLocale('fr_FR');
        $this->translation->addTranslations('fr_FR', [
            'Hello %s' => 'Bonjour %s',
        ]);
        
        $result = $this->translation->translate('Hello %s', 'John');
        
        expect($result)->toBe('Bonjour John');
    });
    
    it('has __ alias for translate', function () {
        $this->translation->addTranslations('en_US', [
            'Test' => 'Tested',
        ]);
        
        $result = $this->translation->__('Test');
        
        expect($result)->toBe('Tested');
    });
    
    it('can load PHP translation file', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'trans') . '.php';
        file_put_contents($tempFile, '<?php return ["Hello" => "Bonjour"];');
        
        $success = $this->translation->loadTranslationFile('fr_FR', $tempFile);
        
        expect($success)->toBeTrue();
        expect($this->translation->hasTranslation('Hello', 'fr_FR'))->toBeTrue();
        
        unlink($tempFile);
    });
    
    it('can load CSV translation file', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'trans') . '.csv';
        file_put_contents($tempFile, "Hello,Bonjour\nWorld,Monde");
        
        $success = $this->translation->loadTranslationFile('fr_FR', $tempFile);
        
        expect($success)->toBeTrue();
        expect($this->translation->hasTranslation('Hello', 'fr_FR'))->toBeTrue();
        expect($this->translation->hasTranslation('World', 'fr_FR'))->toBeTrue();
        
        unlink($tempFile);
    });
    
    it('returns false for non-existent file', function () {
        $success = $this->translation->loadTranslationFile('fr_FR', '/non/existent/file.php');
        
        expect($success)->toBeFalse();
    });
    
    it('does not load same file twice', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'trans') . '.php';
        file_put_contents($tempFile, '<?php return ["Test" => "First"];');
        
        $this->translation->loadTranslationFile('en_US', $tempFile);
        
        // Modify file
        file_put_contents($tempFile, '<?php return ["Test" => "Second"];');
        
        // Load again - should skip
        $this->translation->loadTranslationFile('en_US', $tempFile);
        
        $this->translation->setLocale('en_US');
        $result = $this->translation->translate('Test');
        
        expect($result)->toBe('First'); // Still uses first version
        
        unlink($tempFile);
    });
    
    it('can get all translations for locale', function () {
        $this->translation->addTranslations('fr_FR', [
            'Hello' => 'Bonjour',
            'Goodbye' => 'Au revoir',
        ]);
        
        $translations = $this->translation->getTranslations('fr_FR');
        
        expect($translations)->toHaveCount(2);
        expect($translations)->toHaveKey('Hello');
        expect($translations)->toHaveKey('Goodbye');
    });
    
    it('can pluralize text', function () {
        $single = $this->translation->pluralize(1, 'item');
        $multiple = $this->translation->pluralize(5, 'item');
        
        expect($single)->toBe('item');
        expect($multiple)->toBe('items');
    });
    
    it('can pluralize with custom plural form', function () {
        $single = $this->translation->pluralize(1, 'person', 'people');
        $multiple = $this->translation->pluralize(5, 'person', 'people');
        
        expect($single)->toBe('person');
        expect($multiple)->toBe('people');
    });
    
    it('can translate with count', function () {
        $result = $this->translation->translateWithCount(5, 'item');
        
        expect($result)->toBe('5 items');
    });
    
    it('translates pluralized text', function () {
        $this->translation->setLocale('fr_FR');
        $this->translation->addTranslations('fr_FR', [
            'item' => 'élément',
            'items' => 'éléments',
        ]);
        
        $result = $this->translation->translateWithCount(5, 'item');
        
        expect($result)->toBe('5 éléments');
    });
    
    it('can clear all translations', function () {
        $this->translation->addTranslations('fr_FR', ['Hello' => 'Bonjour']);
        
        expect($this->translation->hasTranslation('Hello', 'fr_FR'))->toBeTrue();
        
        $this->translation->clear();
        
        expect($this->translation->hasTranslation('Hello', 'fr_FR'))->toBeFalse();
    });
    
    it('tracks loaded files', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'trans') . '.php';
        file_put_contents($tempFile, '<?php return ["Test" => "Value"];');
        
        $this->translation->loadTranslationFile('en_US', $tempFile);
        
        $loaded = $this->translation->getLoadedFiles();
        
        expect($loaded)->toContain($tempFile);
        
        unlink($tempFile);
    });
    
    it('merges translations for same locale', function () {
        $this->translation->addTranslations('fr_FR', ['Hello' => 'Bonjour']);
        $this->translation->addTranslations('fr_FR', ['Goodbye' => 'Au revoir']);
        
        $translations = $this->translation->getTranslations('fr_FR');
        
        expect($translations)->toHaveCount(2);
    });
    
    it('handles multiple locales', function () {
        $this->translation->addTranslations('fr_FR', ['Hello' => 'Bonjour']);
        $this->translation->addTranslations('es_ES', ['Hello' => 'Hola']);
        
        $this->translation->setLocale('fr_FR');
        $french = $this->translation->translate('Hello');
        
        $this->translation->setLocale('es_ES');
        $spanish = $this->translation->translate('Hello');
        
        expect($french)->toBe('Bonjour');
        expect($spanish)->toBe('Hola');
    });
});
