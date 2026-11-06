<?php

declare(strict_types=1);

use App\Facades\CommandHelperFacade;

// delete records from activity_log table older than one year
CommandHelperFacade::customMonthlyCommand(
    'activitylog:clean --days=365',
    1,
    '00:00'
);

CommandHelperFacade::customHourlyCommand(
    'pulse:restart',
    47
);

CommandHelperFacade::customDailyCommand(
    'weather:dispatch-parcel-jobs',
    '06:00'
);

CommandHelperFacade::customDailyCommand(
    'fetch:alerts',
    '06:30'
);

CommandHelperFacade::customDailyCommand(
    'fetch:pest-prediction',
    '07:00'
);
