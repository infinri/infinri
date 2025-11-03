<?php

declare(strict_types=1);

namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Message\MessageManager;

/**
 * Provides presentation logic for displaying flash messages
 */
class Messages
{
    /**
     * @var MessageManager
     */
    private MessageManager $messageManager;

    /**
     * Constructor
     *
     * @param MessageManager $messageManager
     */
    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Get all messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messageManager->getMessages(true);
    }

    /**
     * Get messages grouped by type
     *
     * @return array
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
     * Check if there are any messages
     *
     * @return bool
     */
    public function hasMessages(): bool
    {
        return $this->messageManager->hasMessages();
    }

    /**
     * Get success messages
     *
     * @return array
     */
    public function getSuccessMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_SUCCESS);
    }

    /**
     * Get error messages
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_ERROR);
    }

    /**
     * Get warning messages
     *
     * @return array
     */
    public function getWarningMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_WARNING);
    }

    /**
     * Get info messages
     *
     * @return array
     */
    public function getInfoMessages(): array
    {
        return $this->messageManager->getMessagesByType(MessageManager::TYPE_INFO);
    }

    /**
     * Get message count
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->messageManager->getCount();
    }

    /**
     * Get icon class for message type
     *
     * @param string $type
     * @return string
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
     * Get ARIA role for message type
     *
     * @param string $type
     * @return string
     */
    public function getAriaRole(string $type): string
    {
        return $type === MessageManager::TYPE_ERROR ? 'alert' : 'status';
    }
}
