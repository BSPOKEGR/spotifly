# orchestrator

This repository contains all tha parts that are required to deploy the full stack of project

## Installation Notes
- setup each project on each own repository (dockerfile, settings, .env etc)
- modify docker compose .env accordingly (replace values of env.example and copy to .env)
- build and up
```
docker compose up -d --build
```

## Xdebug
Add a breakpoint in the code and run the following command
```bash
xdebug_break();
```

Run xdebug with cli
```
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=demo-api.bspoke.local" php artisan dummy-command
```
