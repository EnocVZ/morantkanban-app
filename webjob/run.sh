#!/bin/bash
cd /home/site/wwwroot
#php artisan schedule:run >> /home/LogFiles/cron.log 2>&1
/usr/local/bin/php artisan schedule:run \ >> /home/LogFiles/cron.log 2>&1