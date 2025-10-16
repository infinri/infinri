<?php

declare(strict_types=1);

namespace Tests\Unit\Theme\ViewModel;

use Infinri\Core\Model\Message\MessageManager;
use Infinri\Theme\ViewModel\Messages;
use PHPUnit\Framework\TestCase;

class MessagesTest extends TestCase
{
    private Messages $viewModel;
    private MessageManager $messageManager;

    protected function setUp(): void
    {
        $this->messageManager = new MessageManager();
        $this->messageManager->clearMessages();
        
        $this->viewModel = new Messages($this->messageManager);
    }

    protected function tearDown(): void
    {
        $this->messageManager->clearMessages();
    }

    public function test_get_messages_returns_array(): void
    {
        $this->messageManager->addSuccess('Test message');

        $messages = $this->viewModel->getMessages();

        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
    }

    public function test_has_messages_returns_false_when_empty(): void
    {
        $this->assertFalse($this->viewModel->hasMessages());
    }

    public function test_has_messages_returns_true_when_messages_exist(): void
    {
        $this->messageManager->addInfo('Test');

        $this->assertTrue($this->viewModel->hasMessages());
    }

    public function test_get_success_messages(): void
    {
        $this->messageManager->addSuccess('Success 1');
        $this->messageManager->addError('Error 1');
        $this->messageManager->addSuccess('Success 2');

        $successMessages = $this->viewModel->getSuccessMessages();

        $this->assertCount(2, $successMessages);
    }

    public function test_get_error_messages(): void
    {
        $this->messageManager->addError('Error 1');
        $this->messageManager->addSuccess('Success 1');

        $errorMessages = $this->viewModel->getErrorMessages();

        $this->assertCount(1, $errorMessages);
    }

    public function test_get_warning_messages(): void
    {
        $this->messageManager->addWarning('Warning 1');

        $warningMessages = $this->viewModel->getWarningMessages();

        $this->assertCount(1, $warningMessages);
    }

    public function test_get_info_messages(): void
    {
        $this->messageManager->addInfo('Info 1');

        $infoMessages = $this->viewModel->getInfoMessages();

        $this->assertCount(1, $infoMessages);
    }

    public function test_get_grouped_messages(): void
    {
        $this->messageManager->addSuccess('Success');
        $this->messageManager->addError('Error');
        $this->messageManager->addWarning('Warning');

        $grouped = $this->viewModel->getGroupedMessages();

        $this->assertIsArray($grouped);
        $this->assertArrayHasKey('success', $grouped);
        $this->assertArrayHasKey('error', $grouped);
        $this->assertArrayHasKey('warning', $grouped);
        $this->assertCount(1, $grouped['success']);
    }

    public function test_get_count(): void
    {
        $this->messageManager->addSuccess('Success');
        $this->messageManager->addError('Error');

        $this->assertEquals(2, $this->viewModel->getCount());
    }

    public function test_get_icon_class_for_success(): void
    {
        $iconClass = $this->viewModel->getIconClass(MessageManager::TYPE_SUCCESS);

        $this->assertEquals('icon-check-circle', $iconClass);
    }

    public function test_get_icon_class_for_error(): void
    {
        $iconClass = $this->viewModel->getIconClass(MessageManager::TYPE_ERROR);

        $this->assertEquals('icon-x-circle', $iconClass);
    }

    public function test_get_icon_class_for_warning(): void
    {
        $iconClass = $this->viewModel->getIconClass(MessageManager::TYPE_WARNING);

        $this->assertEquals('icon-alert-triangle', $iconClass);
    }

    public function test_get_icon_class_for_info(): void
    {
        $iconClass = $this->viewModel->getIconClass(MessageManager::TYPE_INFO);

        $this->assertEquals('icon-info-circle', $iconClass);
    }

    public function test_get_aria_role_for_error(): void
    {
        $role = $this->viewModel->getAriaRole(MessageManager::TYPE_ERROR);

        $this->assertEquals('alert', $role);
    }

    public function test_get_aria_role_for_other_types(): void
    {
        $role = $this->viewModel->getAriaRole(MessageManager::TYPE_SUCCESS);

        $this->assertEquals('status', $role);
    }
}
