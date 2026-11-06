<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Facades\App;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * SlackLogger.
 *
 * By default
 * Errors of level 'critical' will be sent as slack notification.
 */
final class SlackLogger
{
    /**
     * Channel name.
     */
    private const string CHANNEL_NAME = 'slack';

    /**
     * The log level.
     */
    private const Level LEVEL = Level::Critical;

    /**
     * Keep logs for last 14 days.
     */
    private const int DAYS = 14;

    /**
     * Deduplication time.
     */
    private const int DEDUP_TIME = 120;

    /**
     * Log path.
     */
    private const string DEDUP_PATH = 'logs/dedup.log';

    /**
     * Log path.
     */
    private const string PATH = 'logs/slack.log';

    /**
     * @param array{
     *     url: string,
     *     channel_name: string|null,
     *     slack_channel: string|null,
     *     level: string|null,
     *     username: string,
     *     emoji: string,
     *     days: int|null,
     *     path: string|null,
     *     permission: int|null,
     * } $config
     * @throws MissingExtensionException
     */
    public function __invoke(array $config): ?Logger
    {
        // parse values from config if exist, otherwise use defaults.
        $channelName = $config['channel_name'] ?? self::CHANNEL_NAME;
        $slackChannelName = $config['slack_channel'] ?? null;
        $level = $config['level'] ?? self::LEVEL;
        $url = $config['url'];
        $username = $config['username'];
        $emoji = $config['emoji'];

        // file handler config
        // parse values from config if exist, otherwise use defaults.
        $days = $config['days'] ?? self::DAYS;
        $path = $config['path'] ?? storage_path(self::PATH);
        $filePermission = $config['permission'] ?? null;

        /** phpstan-ignore-next-line */
        $slackWebhookHandler = new SlackWebhookHandler($url, $slackChannelName, $username, true, $emoji, false, true, $level);
        $slackWebhookHandler->pushProcessor(function ($record) {
            // default formatter is broken
            // send only data that matter.
            /** phpstan-ignore-next-line */
            if (!empty($record['context']['exception']) && $record['context']['exception'] instanceof \Exception) {
                $e = $record['context']['exception'];
                $record['context']['exception'] = \sprintf("- Class: %s\n- Code: %s\n- Message: %s\n- File: %s\n- Line: %s", \get_class($e), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            }

            return $record;
        });

        /** phpstan-ignore-next-line */
        $fileHandler = new RotatingFileHandler($path, $days, $level, true, $filePermission);

        $environment = App::environment();

        // if env is testing just log in file, do not send to slack
        // WhatFailureGroupHandler suppress any error of handlers
        if ('testing' === $environment) {
            $finalHandler = new WhatFailureGroupHandler([$fileHandler]);
        } else {
            $finalHandler = new WhatFailureGroupHandler([$fileHandler, $slackWebhookHandler]);
        }

        /** phpstan-ignore-next-line */
        $deduplicationHandler = new DeduplicationHandler($finalHandler, storage_path(self::DEDUP_PATH), $level, self::DEDUP_TIME);

        return new Logger($channelName, [$deduplicationHandler]);
    }
}
