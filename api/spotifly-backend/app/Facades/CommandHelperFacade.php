<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void customMonthlyCommand(string $commandName, int $scheduledDay, string $scheduledTime, ?string $commandUrlOverride = null)
 * @method static void customHourlyCommand(string $commandName, int $scheduledTime, ?string $commandUrlOverride = null)
 */
class CommandHelperFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommandHelperFacade::class;
    }
}
