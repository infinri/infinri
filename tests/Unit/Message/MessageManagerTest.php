<?php

declare(strict_types=1);

namespace Tests\Unit\Message;

use Infinri\Core\Model\Message\MessageManager;
use PHPUnit\Framework\TestCase;

class MessageManagerTest extends TestCase
{
    private MessageManager $messageManager;

    protected function setUp(): void
    {
        $this->messageManager = new MessageManager();
        
        // Clear any existing messages
        $this->messageManager->clearMessages();
    }

    protected function tearDown(): void
    {
        // Clean up session
        $this->messageManager->clearMessages();
    }

    public function test_add_success_message(): void
    {
        $this->messageManager->addSuccess('Success message');

        $this->assertTrue($this->messageManager->hasMessages());
        $this->assertEquals(1, $this->messageManager->getCount());
    }

    public function test_add_error_message(): void
    {
        $this->messageManager->addError('Error message');

        $this->assertTrue($this->messageManager->hasMessages());
        $this->assertTrue($this->messageManager->hasMessages(MessageManager::TYPE_ERROR));
    }

    public function test_add_warning_message(): void
    {
        $this->messageManager->addWarning('Warning message');

        $this->assertTrue($this->messageManager->hasMessages());
        $this->assertEquals(1, $this->messageManager->getCount(MessageManager::TYPE_WARNING));
    }

    public function test_add_info_message(): void
    {
        $this->messageManager->addInfo('Info message');

        $this->assertTrue($this->messageManager->hasMessages());
    }

    public function test_get_messages_returns_array(): void
    {
        $this->messageManager->addSuccess('Test message');

        $messages = $this->messageManager->getMessages(false);

        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('success', $messages[0]['type']);
        $this->assertEquals('Test message', $messages[0]['text']);
    }

    public function test_get_messages_clears_by_default(): void
    {
        $this->messageManager->addSuccess('Test message');

        $messages = $this->messageManager->getMessages();

        $this->assertCount(1, $messages);
        $this->assertFalse($this->messageManager->hasMessages());
    }

    public function test_get_messages_does_not_clear_when_specified(): void
    {
        $this->messageManager->addSuccess('Test message');

        $messages = $this->messageManager->getMessages(false);

        $this->assertCount(1, $messages);
        $this->assertTrue($this->messageManager->hasMessages());
    }

    public function test_get_messages_by_type(): void
    {
        $this->messageManager->addSuccess('Success 1');
        $this->messageManager->addError('Error 1');
        $this->messageManager->addSuccess('Success 2');

        $successMessages = $this->messageManager->getMessagesByType(MessageManager::TYPE_SUCCESS, false);

        $this->assertCount(2, $successMessages);
        $this->assertEquals('Success 1', $successMessages[0]['text']);
        $this->assertEquals('Success 2', $successMessages[1]['text']);
    }

    public function test_get_messages_by_type_clears_only_that_type(): void
    {
        $this->messageManager->addSuccess('Success');
        $this->messageManager->addError('Error');

        $successMessages = $this->messageManager->getMessagesByType(MessageManager::TYPE_SUCCESS);

        $this->assertCount(1, $successMessages);
        $this->assertTrue($this->messageManager->hasMessages());
        $this->assertFalse($this->messageManager->hasMessages(MessageManager::TYPE_SUCCESS));
        $this->assertTrue($this->messageManager->hasMessages(MessageManager::TYPE_ERROR));
    }

    public function test_has_messages_returns_false_when_empty(): void
    {
        $this->assertFalse($this->messageManager->hasMessages());
    }

    public function test_has_messages_returns_true_when_messages_exist(): void
    {
        $this->messageManager->addInfo('Test');

        $this->assertTrue($this->messageManager->hasMessages());
    }

    public function test_has_messages_can_check_by_type(): void
    {
        $this->messageManager->addSuccess('Success');

        $this->assertTrue($this->messageManager->hasMessages(MessageManager::TYPE_SUCCESS));
        $this->assertFalse($this->messageManager->hasMessages(MessageManager::TYPE_ERROR));
    }

    public function test_clear_messages(): void
    {
        $this->messageManager->addSuccess('Success');
        $this->messageManager->addError('Error');

        $this->messageManager->clearMessages();

        $this->assertFalse($this->messageManager->hasMessages());
        $this->assertEquals(0, $this->messageManager->getCount());
    }

    public function test_get_count(): void
    {
        $this->messageManager->addSuccess('Success 1');
        $this->messageManager->addSuccess('Success 2');
        $this->messageManager->addError('Error 1');

        $this->assertEquals(3, $this->messageManager->getCount());
        $this->assertEquals(2, $this->messageManager->getCount(MessageManager::TYPE_SUCCESS));
        $this->assertEquals(1, $this->messageManager->getCount(MessageManager::TYPE_ERROR));
    }

    public function test_add_multiple_messages(): void
    {
        $this->messageManager
            ->addSuccess('Success')
            ->addError('Error')
            ->addWarning('Warning')
            ->addInfo('Info');

        $this->assertEquals(4, $this->messageManager->getCount());
    }

    public function test_messages_have_timestamp(): void
    {
        $this->messageManager->addSuccess('Test');

        $messages = $this->messageManager->getMessages(false);

        $this->assertArrayHasKey('timestamp', $messages[0]);
        $this->assertIsInt($messages[0]['timestamp']);
    }
}
