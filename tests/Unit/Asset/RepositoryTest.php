<?php

declare(strict_types=1);

use Infinri\Core\Model\Asset\Repository;

describe('Asset Repository', function () {
    
    beforeEach(function () {
        $this->repository = new Repository();
    });
    
    it('can add CSS asset', function () {
        $this->repository->addCss('Infinri_Core::css/style.css');
        
        $css = $this->repository->getAllCss();
        
        expect($css)->toHaveCount(1);
        expect($css)->toHaveKey('Infinri_Core::css/style.css');
        expect($css['Infinri_Core::css/style.css']['path'])->toBe('Infinri_Core::css/style.css');
    });
    
    it('can add JavaScript asset', function () {
        $this->repository->addJs('Infinri_Core::js/app.js');
        
        $js = $this->repository->getAllJs();
        
        expect($js)->toHaveCount(1);
        expect($js)->toHaveKey('Infinri_Core::js/app.js');
        expect($js['Infinri_Core::js/app.js']['path'])->toBe('Infinri_Core::js/app.js');
    });
    
    it('can add CSS with attributes', function () {
        $this->repository->addCss('Infinri_Core::css/print.css', [
            'media' => 'print',
            'rel' => 'stylesheet'
        ]);
        
        $css = $this->repository->getAllCss();
        
        expect($css['Infinri_Core::css/print.css']['attributes'])->toBe([
            'media' => 'print',
            'rel' => 'stylesheet'
        ]);
    });
    
    it('can add JavaScript with attributes', function () {
        $this->repository->addJs('Infinri_Core::js/async.js', [
            'async' => true,
            'defer' => false
        ]);
        
        $js = $this->repository->getAllJs();
        
        expect($js['Infinri_Core::js/async.js']['attributes'])->toBe([
            'async' => true,
            'defer' => false
        ]);
    });
    
    it('can add multiple CSS assets', function () {
        $this->repository->addCss('Infinri_Core::css/style.css');
        $this->repository->addCss('Infinri_Theme::css/theme.css');
        $this->repository->addCss('Infinri_Core::css/responsive.css');
        
        $css = $this->repository->getAllCss();
        
        expect($css)->toHaveCount(3);
    });
    
    it('can add multiple JavaScript assets', function () {
        $this->repository->addJs('Infinri_Core::js/app.js');
        $this->repository->addJs('Infinri_Theme::js/theme.js');
        
        $js = $this->repository->getAllJs();
        
        expect($js)->toHaveCount(2);
    });
    
    it('sorts CSS assets by priority', function () {
        $this->repository->addCss('Infinri_Core::css/style.css', [], 10);
        $this->repository->addCss('Infinri_Theme::css/theme.css', [], 5);
        $this->repository->addCss('Infinri_Core::css/reset.css', [], 0);
        
        $css = $this->repository->getAllCss();
        $paths = array_column($css, 'path');
        
        expect($paths[0])->toBe('Infinri_Core::css/reset.css'); // priority 0
        expect($paths[1])->toBe('Infinri_Theme::css/theme.css'); // priority 5
        expect($paths[2])->toBe('Infinri_Core::css/style.css'); // priority 10
    });
    
    it('sorts JavaScript assets by priority', function () {
        $this->repository->addJs('Infinri_Core::js/app.js', [], 20);
        $this->repository->addJs('Infinri_Core::js/jquery.js', [], 0);
        $this->repository->addJs('Infinri_Theme::js/theme.js', [], 10);
        
        $js = $this->repository->getAllJs();
        $paths = array_column($js, 'path');
        
        expect($paths[0])->toBe('Infinri_Core::js/jquery.js'); // priority 0
        expect($paths[1])->toBe('Infinri_Theme::js/theme.js'); // priority 10
        expect($paths[2])->toBe('Infinri_Core::js/app.js'); // priority 20
    });
    
    it('can remove CSS asset', function () {
        $this->repository->addCss('Infinri_Core::css/style.css');
        $this->repository->addCss('Infinri_Theme::css/theme.css');
        
        $this->repository->removeCss('Infinri_Core::css/style.css');
        
        $css = $this->repository->getAllCss();
        
        expect($css)->toHaveCount(1);
        expect($css)->not->toHaveKey('Infinri_Core::css/style.css');
        expect($css)->toHaveKey('Infinri_Theme::css/theme.css');
    });
    
    it('can remove JavaScript asset', function () {
        $this->repository->addJs('Infinri_Core::js/app.js');
        $this->repository->addJs('Infinri_Theme::js/theme.js');
        
        $this->repository->removeJs('Infinri_Core::js/app.js');
        
        $js = $this->repository->getAllJs();
        
        expect($js)->toHaveCount(1);
        expect($js)->not->toHaveKey('Infinri_Core::js/app.js');
        expect($js)->toHaveKey('Infinri_Theme::js/theme.js');
    });
    
    it('can clear all assets', function () {
        $this->repository->addCss('Infinri_Core::css/style.css');
        $this->repository->addCss('Infinri_Theme::css/theme.css');
        $this->repository->addJs('Infinri_Core::js/app.js');
        $this->repository->addJs('Infinri_Theme::js/theme.js');
        
        $this->repository->clear();
        
        expect($this->repository->getAllCss())->toBeEmpty();
        expect($this->repository->getAllJs())->toBeEmpty();
    });
    
    it('handles duplicate asset paths by overwriting', function () {
        $this->repository->addCss('Infinri_Core::css/style.css', ['media' => 'screen'], 10);
        $this->repository->addCss('Infinri_Core::css/style.css', ['media' => 'print'], 5);
        
        $css = $this->repository->getAllCss();
        
        expect($css)->toHaveCount(1);
        expect($css['Infinri_Core::css/style.css']['attributes']['media'])->toBe('print');
        expect($css['Infinri_Core::css/style.css']['priority'])->toBe(5);
    });
    
    it('returns empty array when no CSS assets registered', function () {
        expect($this->repository->getAllCss())->toBeEmpty();
    });
    
    it('returns empty array when no JavaScript assets registered', function () {
        expect($this->repository->getAllJs())->toBeEmpty();
    });
});
