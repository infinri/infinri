<?php

declare(strict_types=1);

use Infinri\Core\Helper\Data;

describe('Data Helper', function () {
    
    beforeEach(function () {
        $this->helper = new Data();
    });
    
    it('can check if value is empty', function () {
        expect($this->helper->isEmpty(null))->toBeTrue();
        expect($this->helper->isEmpty(''))->toBeTrue();
        expect($this->helper->isEmpty([]))->toBeTrue();
        expect($this->helper->isEmpty('test'))->toBeFalse();
        expect($this->helper->isEmpty(0))->toBeFalse();
        expect($this->helper->isEmpty(false))->toBeFalse();
    });
    
    it('can get array value by path', function () {
        $array = [
            'user' => [
                'name' => 'John',
                'address' => [
                    'city' => 'New York'
                ]
            ]
        ];
        
        expect($this->helper->getArrayValue($array, 'user.name'))->toBe('John');
        expect($this->helper->getArrayValue($array, 'user.address.city'))->toBe('New York');
    });
    
    it('returns default value for non-existent path', function () {
        $array = ['foo' => 'bar'];
        
        expect($this->helper->getArrayValue($array, 'baz', 'default'))->toBe('default');
    });
    
    it('can set array value by path', function () {
        $array = [];
        
        $this->helper->setArrayValue($array, 'user.name', 'John');
        $this->helper->setArrayValue($array, 'user.age', 30);
        
        expect($array['user']['name'])->toBe('John');
        expect($array['user']['age'])->toBe(30);
    });
    
    it('can convert array to XML', function () {
        $array = ['name' => 'John', 'age' => 30];
        
        $xml = $this->helper->arrayToXml($array, 'user');
        
        expect($xml)->toContain('<user>');
        expect($xml)->toContain('<name>John</name>');
        expect($xml)->toContain('<age>30</age>');
    });
    
    it('can flatten nested array', function () {
        $array = [
            'user' => [
                'name' => 'John',
                'address' => [
                    'city' => 'NYC'
                ]
            ]
        ];
        
        $flattened = $this->helper->flattenArray($array);
        
        expect($flattened)->toHaveKey('user.name');
        expect($flattened)->toHaveKey('user.address.city');
        expect($flattened['user.name'])->toBe('John');
    });
    
    it('can format bytes to human-readable size', function () {
        expect($this->helper->formatBytes(1024))->toBe('1 KB');
        expect($this->helper->formatBytes(1048576))->toBe('1 MB');
        expect($this->helper->formatBytes(1073741824))->toBe('1 GB');
    });
    
    it('can generate random string', function () {
        $string = $this->helper->randomString(16);
        
        expect($string)->toHaveLength(16);
        expect($string)->toMatch('/^[a-zA-Z0-9]+$/');
    });
    
    it('can truncate string', function () {
        $string = 'This is a long string';
        
        expect($this->helper->truncate($string, 10))->toBe('This is...');
        expect($this->helper->truncate($string, 100))->toBe($string);
    });
    
    it('can convert string to slug', function () {
        expect($this->helper->slug('Hello World'))->toBe('hello-world');
        expect($this->helper->slug('Test & Example!'))->toBe('test-example');
        expect($this->helper->slug('Multiple   Spaces'))->toBe('multiple-spaces');
    });
    
    it('can check if string starts with', function () {
        expect($this->helper->startsWith('hello world', 'hello'))->toBeTrue();
        expect($this->helper->startsWith('hello world', 'world'))->toBeFalse();
    });
    
    it('can check if string ends with', function () {
        expect($this->helper->endsWith('hello world', 'world'))->toBeTrue();
        expect($this->helper->endsWith('hello world', 'hello'))->toBeFalse();
    });
    
    it('can convert camelCase to snake_case', function () {
        expect($this->helper->camelToSnake('camelCase'))->toBe('camel_case');
        expect($this->helper->camelToSnake('thisIsATest'))->toBe('this_is_a_test');
    });
    
    it('can convert snake_case to camelCase', function () {
        expect($this->helper->snakeToCamel('snake_case'))->toBe('snakeCase');
        expect($this->helper->snakeToCamel('this_is_a_test'))->toBe('thisIsATest');
    });
    
    it('can deep clone object', function () {
        $object = new stdClass();
        $object->name = 'test';
        
        $clone = $this->helper->deepClone($object);
        
        expect($clone)->not->toBe($object);
        expect($clone->name)->toBe('test');
        
        $clone->name = 'changed';
        expect($object->name)->toBe('test');
    });
});
