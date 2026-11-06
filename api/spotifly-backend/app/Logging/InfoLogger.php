<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Info Logger.
 *
 * By default
 * Minimum Error level debug.
 * Maximum Error level notice
 */
final class InfoLogger
{
    private const int DAYS = 5;
    private const string CHANNEL_NAME = 'info';
    private const Level MINIMUM_LEVEL = Level::Debug;
    private const Level MAXIMUM_LEVEL = Level::Notice;

    /**
     * Log path.
     *
     * @var string
     */
    private const PATH = 'logs/info.log';

    /**
     * @param array{
     *     channel_name: string|null,
     *     minimum_level: string|null,
     *     maximum_level: string|null,
     *     days: int|null,
     *     path: string|null,
     *     permission: int|null,
     * } $config
     */
    public function __invoke(array $config): Logger
    {
        // parse values from config if exist, otherwise use defaults.
        $channelName = $config['channel_name'] ?? self::CHANNEL_NAME;
        $minLevel = $config['minimum_level'] ?? self::MINIMUM_LEVEL;
        $maxLevel = $config['maximum_level'] ?? self::MAXIMUM_LEVEL;
        $days = $config['days'] ?? self::DAYS;
        $path = $config['path'] ?? storage_path(self::PATH);
        $filePermission = $config['permission'] ?? null;

        /** phpstan-ignore-next-line */
        $handler = new RotatingFileHandler($path, $days, Level::Debug, true, $filePermission);

        // filter only the required logs
        /** phpstan-ignore-next-line */
        $filterHandler = new FilterHandler($handler, $minLevel, $maxLevel);

        return new Logger($channelName, [$filterHandler]);
    }
}
