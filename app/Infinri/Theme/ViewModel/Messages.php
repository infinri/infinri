<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Message\MessageManager;

/**
 * Provides presentation logic for displaying flash messages.
 */
class Messages
{
    private MessageManager $messageManager;

    /**
     * Constructor.
     */
    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Get all messages.
     */
    public function getMessages(): array
    {
        return $this->messageManager->getMessages(true);
    }

    /**
     * Get messages grouped by type.
     */
    public function getGroupedMessages(): array
    {
        $messages = $this->messageManager->getMessages(false);
        $grouped = [
            MessageManager::TYPE_SUCCESS => [],
            MessageManager::TYPE_ERROR => [],
            MessageManager::TYPE_WARNING => [],
            MessageManager::TYPE_INFO => [],
        ];

        foreach ($messages as $message) {
            $type = $message['type'] ?? MessageManager::TYPE_INFO;
            $grouped[$type][] = $message;
        }

        // Clear after grouping
        $this->messageManager->clearMessages();

        return array_filter($grouped); // Remove empty types
    }

    /**
     * Check if there are any messages.
     */
    public function hasMessages(): bool
    {
        return $this->messageManager->hasMessages();
    }

    /**
     * Get success messages.
     */
    public function getSuccessMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_SUCCESS);
    }

    /**
     * Get error messages.
     */
    public function getErrorMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_ERROR);
    }

    /**
     * Get warning messages.
     */
    public function getWarningMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_WARNING);
    }

    /**
     * Get info messages.
     */
    public function getInfoMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_INFO);
    }

    /**
     * Get message count.
     */
    public function getCount(): int
    {
        return $this->messageManager->getCount();
    }

    /**
     * Get icon class for message type.
     */
    public function getIconClass(string $type): string
    {
        $icons = [
            MessageManager::TYPE_SUCCESS => 'icon-check-circle',
            MessageManager::TYPE_ERROR => 'icon-x-circle',
            MessageManager::TYPE_WARNING => 'icon-alert-triangle',
            MessageManager::TYPE_INFO => 'icon-info-circle',
        ];

        return $icons[$type] ?? 'icon-info-circle';
    }

    /**
     * Get ARIA role for message type.
     */
    public function getAriaRole(string $type): string
    {
        return MessageManager::TYPE_ERROR === $type ? 'alert' : 'status';
    }
}
