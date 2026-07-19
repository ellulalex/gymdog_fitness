<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Content automation (spec §9): generate weekly, publish due (past the brake)
// hourly. Run in human-approval mode (CONTENT_AUTO_PUBLISH=false) until the
// output has earned auto-publishing.
Schedule::command('content:generate')->weekly();
Schedule::command('content:publish-due')->hourly();
