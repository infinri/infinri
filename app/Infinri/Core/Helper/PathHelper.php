<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

/**
 * Centralized path calculations
 */
class PathHelper
{
    /**
     * Get application root path
     *
     * @return string Path to /app directory
     */
    public static function getAppPath(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Get public directory path
     *
     * @return string Path to /pub directory
     */
    public static function getPubPath(): string
    {
        return dirname(__DIR__, 4) . '/pub';
    }

    /**
     * Get media directory path
     *
     * @return string Path to /pub/media directory
     */
    public static function getMediaPath(): string
    {
        return self::getPubPath() . '/media';
    }

    /**
     * Get static files directory path
     *
     * @return string Path to /pub/static directory
     */
    public static function getStaticPath(): string
    {
        return self::getPubPath() . '/static';
    }

    /**
     * Get var directory path
     *
     * @return string Path to /var directory
     */
    public static function getVarPath(): string
    {
        return dirname(__DIR__, 4) . '/var';
    }

    /**
     * Get cache directory path
     *
     * @return string Path to /var/cache directory
     */
    public static function getCachePath(): string
    {
        return self::getVarPath() . '/cache';
    }

    /**
     * Get logs directory path
     *
     * @return string Path to /var/log directory
     */
    public static function getLogPath(): string
    {
        return self::getVarPath() . '/log';
    }
}
