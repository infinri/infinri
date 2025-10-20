<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Logger Helper
 * 
 * Provides centralized logging functionality using Monolog
 */
class Logger
{
    private static ?MonologLogger $logger = null;
    private static string $logPath = '';
    
    /**
     * Initialize logger
     *
     * @param string $logPath
     * @return void
     */
    public static function init(string $logPath): void
    {
        self::$logPath = $logPath;
    }
    
    /**
     * Get logger instance
     *
     * @param string $channel
     * @return MonologLogger
     */
    private static function getLogger(string $channel = 'app'): MonologLogger
    {
        if (self::$logger === null) {
            $logPath = self::$logPath ?: __DIR__ . '/../../../var/log';
            
            self::$logger = new MonologLogger($channel);
            
            // Main log file (rotates daily, keeps 14 days)
            $handler = new RotatingFileHandler(
                $logPath . '/infinri.log',
                14,
                MonologLogger::DEBUG
            );
            
            // Format: [2025-10-16 12:00:00] app.ERROR: Message {"context":"data"}
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s"
            );
            $handler->setFormatter($formatter);
            
            self::$logger->pushHandler($handler);
        }
        
        return self::$logger;
    }
    
    /**
     * Log emergency message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }
    
    /**
     * Log alert message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }
    
    /**
     * Log notice message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }
    
    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }
    
    /**
     * Log exception
     *
     * @param \Throwable $exception
     * @param string $message
     * @return void
     */
    public static function exception(\Throwable $exception, string $message = 'Exception occurred'): void
    {
        self::error($message, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
