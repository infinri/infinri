<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Message;

/**
 * MessageManager
 * 
 * Manages flash messages stored in session
 * Messages are displayed once and then removed
 */
class MessageManager
{
    /**
     * Message types
     */
    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR = 'error';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO = 'info';
    
    /**
     * Session key for messages
     */
    private const SESSION_KEY = 'infinri_messages';
    
    /**
     * Add a success message
     * 
     * @param string $message
     * @return self
     */
    public function addSuccess(string $message): self
    {
        return $this->addMessage(self::TYPE_SUCCESS, $message);
    }
    
    /**
     * Add an error message
     * 
     * @param string $message
     * @return self
     */
    public function addError(string $message): self
    {
        return $this->addMessage(self::TYPE_ERROR, $message);
    }
    
    /**
     * Add a warning message
     * 
     * @param string $message
     * @return self
     */
    public function addWarning(string $message): self
    {
        return $this->addMessage(self::TYPE_WARNING, $message);
    }
    
    /**
     * Add an info message
     * 
     * @param string $message
     * @return self
     */
    public function addInfo(string $message): self
    {
        return $this->addMessage(self::TYPE_INFO, $message);
    }
    
    /**
     * Add a message
     * 
     * @param string $type
     * @param string $message
     * @return self
     */
    public function addMessage(string $type, string $message): self
    {
        $this->startSession();
        
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        $_SESSION[self::SESSION_KEY][] = [
            'type' => $type,
            'text' => $message,
            'timestamp' => time(),
        ];
        
        return $this;
    }
    
    /**
     * Get all messages
     * 
     * @param bool $clear Whether to clear messages after retrieving
     * @return array
     */
    public function getMessages(bool $clear = true): array
    {
        $this->startSession();
        
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        
        if ($clear) {
            $this->clearMessages();
        }
        
        return $messages;
    }
    
    /**
     * Get messages by type
     * 
     * @param string $type
     * @param bool $clear Whether to clear those messages after retrieving
     * @return array
     */
    public function getMessagesByType(string $type, bool $clear = true): array
    {
        $this->startSession();
        
        $allMessages = $_SESSION[self::SESSION_KEY] ?? [];
        $filtered = array_filter($allMessages, fn($msg) => $msg['type'] === $type);
        
        if ($clear && !empty($filtered)) {
            // Remove only the filtered messages
            $_SESSION[self::SESSION_KEY] = array_filter(
                $allMessages,
                fn($msg) => $msg['type'] !== $type
            );
            $_SESSION[self::SESSION_KEY] = array_values($_SESSION[self::SESSION_KEY]);
        }
        
        return array_values($filtered);
    }
    
    /**
     * Check if there are any messages
     * 
     * @param string|null $type Optional type to check
     * @return bool
     */
    public function hasMessages(?string $type = null): bool
    {
        $this->startSession();
        
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        
        if ($type === null) {
            return !empty($messages);
        }
        
        return !empty(array_filter($messages, fn($msg) => $msg['type'] === $type));
    }
    
    /**
     * Clear all messages
     * 
     * @return self
     */
    public function clearMessages(): self
    {
        $this->startSession();
        
        $_SESSION[self::SESSION_KEY] = [];
        
        return $this;
    }
    
    /**
     * Get message count
     * 
     * @param string|null $type Optional type to count
     * @return int
     */
    public function getCount(?string $type = null): int
    {
        $this->startSession();
        
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        
        if ($type === null) {
            return count($messages);
        }
        
        return count(array_filter($messages, fn($msg) => $msg['type'] === $type));
    }
    
    /**
     * Start session if not already started
     * 
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
