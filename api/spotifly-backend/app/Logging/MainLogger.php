<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * MainLogger logger.
 *
 * By default
 * Errors of level 'error' and above will trigger logging of all level errors.
 * Error of level 'warning' will logged but will not trigger logging of any lower level errors.
 */
final class MainLogger
{
    private const int DAYS = 14;
    private const string CHANNEL_NAME = 'error';
    private const Level LEVEL = Level::Warning;
    private const Level ACTION_LEVEL = Level::Error;
    private const string PATH = 'logs/error.log';

    /**
     * @param array{
     *     channel_name: string|null,
     *     level: string|null,
     *     action_level: string|null,
     *     days: int|null,
     *     path: string|null,
     *     permission: int|null,
     * } $config
     */
    public function __invoke(array $config): Logger
    {
        // parse values from config if exist, otherwise use defaults.
        $channelName = $config['channel_name'] ?? self::CHANNEL_NAME;
        $level = $config['level'] ?? self::LEVEL;
        $actionLevel = $config['action_level'] ?? self::ACTION_LEVEL;
        $days = $config['days'] ?? self::DAYS;
        $path = $config['path'] ?? storage_path(self::PATH);
        $filePermission = $config['permission'] ?? null;

        /** phpstan-ignore-next-line */
        $handler = new RotatingFileHandler($path, $days, Level::Debug, true, $filePermission);
        // uncomment to log stacktrace
        // $handler->getFormatter()->includeStacktraces(true);

        // include all logs if activation strategy is triggered
        /** phpstan-ignore-next-line */
        $fingersHandler = new FingersCrossedHandler($handler, new ErrorLevelActivationStrategy($actionLevel), 0, true, true, $level);

        return new Logger($channelName, [$fingersHandler]);
    }
}
