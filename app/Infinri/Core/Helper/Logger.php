<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

/**
 * Log files are organized by severity level:
 * - error.log: ERROR, CRITICAL, ALERT, EMERGENCY
 * - warning.log: WARNING
 * - info.log: INFO, NOTICE
 * - debug.log: All levels (for troubleshooting)
 *
 * When logs reach the configured size (default 10MB), they are automatically
 * compressed and archived with date range: error.log.2025-11-01_2025-11-15.gz
 */
class Logger
{
    private static ?MonologLogger $logger = null;

    private static string $logPath = '';

    /** @var int Maximum log file size in bytes before rotation (default 10MB) */
    private const MAX_LOG_SIZE = 10 * 1024 * 1024;

    /**
     * Initialize logger.
     */
    public static function init(string $logPath): void
    {
        self::$logPath = $logPath;
    }

    /**
     * Check and rotate log files if they exceed maximum size
     * This should be called periodically (e.g., via cron or at application startup).
     */
    public static function rotateLogsIfNeeded(): void
    {
        $logPath = self::$logPath ?: __DIR__ . '/../../../../var/log';
        $logFiles = ['error.log', 'warning.log', 'info.log', 'debug.log'];

        foreach ($logFiles as $logFile) {
            $filePath = $logPath . '/' . $logFile;

            if (! file_exists($filePath)) {
                continue;
            }

            $fileSize = filesize($filePath);

            if ($fileSize >= self::MAX_LOG_SIZE) {
                self::rotateLog($filePath);
            }
        }
    }

    /**
     * Rotate a log file by compressing it with date range and creating a new empty file.
     *
     * @param string $filePath Full path to the log file
     */
    private static function rotateLog(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        // Get first and last log dates from file
        $firstLine = '';
        $lastLine = '';

        $file = fopen($filePath, 'r');
        if ($file) {
            $firstLine = fgets($file);

            // Get last line efficiently
            fseek($file, -1, \SEEK_END);
            $pos = ftell($file);
            $lastLine = '';

            while ($pos > 0) {
                fseek($file, $pos, \SEEK_SET);
                $char = fgetc($file);

                if ("\n" === $char && ! empty($lastLine)) {
                    break;
                }

                $lastLine = $char . $lastLine;
                $pos--;
            }

            fclose($file);
        }

        // Extract dates from log entries [2025-11-02 14:32:10]
        $firstDate = self::extractDateFromLogLine($firstLine ?: '');
        $lastDate = self::extractDateFromLogLine($lastLine ?: '');

        // Generate archive filename: error.log.2025-11-01_2025-11-15.gz
        $dateRange = $firstDate && $lastDate ? ".{$firstDate}_{$lastDate}" : '.' . date('Y-m-d');
        $archivePath = $filePath . $dateRange . '.gz';

        // Compress the log file
        $fp = fopen($filePath, 'r');
        $gz = gzopen($archivePath, 'wb9'); // Maximum compression

        if ($fp && $gz) {
            while (! feof($fp)) {
                $chunk = fread($fp, 1024 * 512);
                if (false !== $chunk) {
                    gzwrite($gz, $chunk);
                }
            }
            fclose($fp);
            gzclose($gz);

            // Create new empty log file
            file_put_contents($filePath, '');
            chmod($filePath, 0666);

            error_log("Logger: Rotated log file {$filePath} to {$archivePath}");
        }
    }

    /**
     * Extract date from log line in format [2025-11-02 14:32:10].
     *
     * @param string $line Log line
     *
     * @return string|null Date in Y-m-d format or null
     */
    private static function extractDateFromLogLine(string $line): ?string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2})/', $line, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get logger instance.
     */
    private static function getLogger(string $channel = 'app'): MonologLogger
    {
        if (null === self::$logger) {
            // Fallback: Use project root var/log (4 levels up from Core/Helper)
            $logPath = self::$logPath ?: __DIR__ . '/../../../../var/log';

            self::$logger = new MonologLogger($channel);

            // Log format: [2025-10-16 12:00:00] app.ERROR: Message {"context":"data"}
            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            );

            // NOTE: Handlers are processed in REVERSE order (last pushed = first checked)
            // So we push in order: debug -> info -> warning -> error

            // DEBUG log - everything (for development/troubleshooting)
            $debugHandler = new StreamHandler(
                $logPath . '/debug.log',
                MonologLogger::DEBUG,
                true, // Bubble to allow other handlers to also log
                0666
            );
            $debugHandler->setFormatter($formatter);
            self::$logger->pushHandler($debugHandler);

            // INFO log - info, notice (warnings/errors go to their own files)
            $infoHandler = new StreamHandler(
                $logPath . '/info.log',
                MonologLogger::INFO,
                true,
                0666
            );
            $infoHandler->setFormatter($formatter);
            self::$logger->pushHandler($infoHandler);

            // WARNING log - warnings only
            $warningHandler = new StreamHandler(
                $logPath . '/warning.log',
                MonologLogger::WARNING,
                true,
                0666
            );
            $warningHandler->setFormatter($formatter);
            self::$logger->pushHandler($warningHandler);

            // ERROR log - errors and above (ERROR, CRITICAL, ALERT, EMERGENCY)
            $errorHandler = new StreamHandler(
                $logPath . '/error.log',
                MonologLogger::ERROR,
                true, // Bubble up
                0666
            );
            $errorHandler->setFormatter($formatter);
            self::$logger->pushHandler($errorHandler);
        }

        return self::$logger;
    }

    /**
     * Log emergency message.
     *
     * @param array<string, mixed> $context
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::getLogger()->emergency($message, $context);
    }

    /**
     * Log alert message.
     *
     * @param array<string, mixed> $context
     */
    public static function alert(string $message, array $context = []): void
    {
        self::getLogger()->alert($message, $context);
    }

    /**
     * Log critical message.
     *
     * @param array<string, mixed> $context
     */
    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }

    /**
     * Log error message.
     *
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    /**
     * Log warning message.
     *
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    /**
     * Log notice message.
     *
     * @param array<string, mixed> $context
     */
    public static function notice(string $message, array $context = []): void
    {
        self::getLogger()->notice($message, $context);
    }

    /**
     * Log info message.
     *
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    /**
     * Log debug message.
     *
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    /**
     * Log exception.
     */
    public static function exception(\Throwable $exception, string $message = 'Exception occurred'): void
    {
        self::error($message, [
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
