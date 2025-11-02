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
     * Session key for messages (namespaced by area)
     */
    private const SESSION_KEY_PREFIX = 'infinri_messages_';
    
    /**
     * Get the session key for current area (admin vs frontend)
     */
    private function getSessionKey(): string
    {
        $isAdmin = isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/admin');
        return self::SESSION_KEY_PREFIX . ($isAdmin ? 'admin' : 'frontend');
    }
    
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
        
        $sessionKey = $this->getSessionKey();
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }
        
        $_SESSION[$sessionKey][] = [
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
        
        $sessionKey = $this->getSessionKey();
        $messages = $_SESSION[$sessionKey] ?? [];
        
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
        
        $sessionKey = $this->getSessionKey();
        $allMessages = $_SESSION[$sessionKey] ?? [];
        $filtered = array_filter($allMessages, fn($msg) => $msg['type'] === $type);
        
        if ($clear && !empty($filtered)) {
            // Remove only the filtered messages
            $_SESSION[$sessionKey] = array_filter(
                $allMessages,
                fn($msg) => $msg['type'] !== $type
            );
            $_SESSION[$sessionKey] = array_values($_SESSION[$sessionKey]);
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
        
        $sessionKey = $this->getSessionKey();
        $messages = $_SESSION[$sessionKey] ?? [];
        
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
        
        $sessionKey = $this->getSessionKey();
        $_SESSION[$sessionKey] = [];
        
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
        
        $sessionKey = $this->getSessionKey();
        $messages = $_SESSION[$sessionKey] ?? [];
        
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
