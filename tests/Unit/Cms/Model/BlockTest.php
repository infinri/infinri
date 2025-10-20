<?php

use Infinri\Cms\Model\Block;
use Infinri\Cms\Model\ResourceModel\Block as BlockResource;

function createBlock(array $data = []): Block
{
    $resourceMock = Mockery::mock(BlockResource::class);
    return new Block($resourceMock, $data);
}

test('can create block instance', function () {
    $block = createBlock();
    expect($block)->toBeInstanceOf(Block::class);
});

test('can set and get block id', function () {
    $block = createBlock();
    $block->setBlockId(5);
    expect($block->getBlockId())->toBe(5);
});

test('can set and get identifier', function () {
    $block = createBlock();
    $block->setIdentifier('welcome_message');
    expect($block->getIdentifier())->toBe('welcome_message');
});

test('can set and get title', function () {
    $block = createBlock();
    $block->setTitle('Welcome Message');
    expect($block->getTitle())->toBe('Welcome Message');
});

test('can set and get content', function () {
    $block = createBlock();
    $block->setContent('<p>Welcome!</p>');
    expect($block->getContent())->toBe('<p>Welcome!</p>');
});

test('can set and check active status', function () {
    $block = createBlock();
    $block->setIsActive(true);
    expect($block->isActive())->toBeTrue();
    
    $block->setIsActive(false);
    expect($block->isActive())->toBeFalse();
});

test('validate requires identifier', function () {
    $block = createBlock();
    $block->setTitle('Test');
    $block->validate();
})->throws(\InvalidArgumentException::class, 'Block identifier is required');

test('validate requires title', function () {
    $block = createBlock();
    $block->setIdentifier('test');
    $block->validate();
})->throws(\InvalidArgumentException::class, 'Block title is required');

test('validate rejects invalid identifier format', function () {
    $block = createBlock();
    $block->setIdentifier('Invalid Identifier!');
    $block->setTitle('Test');
    $block->validate();
})->throws(\InvalidArgumentException::class, 'Identifier can only contain lowercase');

test('validate accepts valid identifier with hyphens', function () {
    $block = createBlock();
    $block->setIdentifier('my-test-block');
    $block->setTitle('Test');
    $block->validate();
    expect(true)->toBeTrue();
});

test('validate accepts valid identifier with underscores', function () {
    $block = createBlock();
    $block->setIdentifier('my_test_block');
    $block->setTitle('Test');
    $block->validate();
    expect(true)->toBeTrue();
});

test('validate accepts valid identifier with numbers', function () {
    $block = createBlock();
    $block->setIdentifier('block-123');
    $block->setTitle('Test');
    $block->validate();
    expect(true)->toBeTrue();
});

test('validate accepts mixed hyphens and underscores', function () {
    $block = createBlock();
    $block->setIdentifier('my_test-block_123');
    $block->setTitle('Test');
    $block->validate();
    expect(true)->toBeTrue();
});
