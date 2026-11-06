<?php

declare(strict_types=1);

namespace App\Libraries;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schedule;

class CommandHelper
{
    public static function customDailyCommand(
        string $commandName,
        string $scheduledTime,
        ?string $commandUrlOverride = null
    ): void {
        $pingKey = Config::get('services.healthchecks.ping_key');

        if (!empty($pingKey)) {
            $healthcheckUrl = Config::get('services.healthchecks.url') . $pingKey . '/';
            $commandUrl = $commandUrlOverride ?? str_replace(':', '-', $commandName);
            Schedule::command($commandName)->dailyAt($scheduledTime)
                ->pingBefore($healthcheckUrl . $commandUrl)
                ->pingOnFailure($healthcheckUrl . $commandUrl . '/fail');
        } else {
            Schedule::command($commandName)->dailyAt($scheduledTime);
        }
    }

    public static function customMonthlyCommand(
        string $commandName,
        int $scheduledDay,
        string $scheduledTime,
        ?string $commandUrlOverride = null
    ): void {
        $pingKey = Config::get('services.healthchecks.ping_key');

        if (!empty($pingKey)) {
            $healthcheckUrl = Config::get('services.healthchecks.url') . $pingKey . '/';
            $commandUrl = $commandUrlOverride ?? str_replace(':', '-', $commandName);
            Schedule::command($commandName)->monthlyOn($scheduledDay, $scheduledTime)
                ->pingBefore($healthcheckUrl . $commandUrl)
                ->pingOnFailure($healthcheckUrl . $commandUrl . '/fail');
        } else {
            Schedule::command($commandName)->monthlyOn($scheduledDay, $scheduledTime);
        }
    }

    public static function customHourlyCommand(
        string $commandName,
        int $scheduledMinute,
        ?string $commandUrlOverride = null
    ): void {
        $pingKey = Config::get('services.healthchecks.ping_key');

        if (!empty($pingKey)) {
            $healthcheckUrl = Config::get('services.healthchecks.url') . $pingKey . '/';
            $commandUrl = $commandUrlOverride ?? str_replace(':', '-', $commandName);
            Schedule::command($commandName)->hourlyAt($scheduledMinute)
                ->pingBefore($healthcheckUrl . $commandUrl)
                ->pingOnFailure($healthcheckUrl . $commandUrl . '/fail');
        } else {
            Schedule::command($commandName)->hourlyAt($scheduledMinute);
        }
    }
}
