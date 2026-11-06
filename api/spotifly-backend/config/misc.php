<?php

return [
    'project_name' => env('PROJECT_NAME'),
    'internal_ip_whitelist' => \explode(',', env('INTERNAL_IP_WHITELIST')),
    'enable_ip_blocker' => env('ENABLE_IP_BLOCKER', true),
];
