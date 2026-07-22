<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Content automation pipeline (spec §9), in human-approval mode
// (CONTENT_AUTO_PUBLISH=false) until the output has earned auto-publishing.
//
// Daily, measured cadence — quality over volume to stay clear of Google's
// scaled-content signals: top the topic queue up, generate one article, and
// publish anything past its brake window. Times are Malta-local; the two
// long-running Claude commands run in the background (a grounded generation
// takes ~15-20 min) so they never block the per-minute scheduler, and
// withoutOverlapping guards against a slow run colliding with the next.
Schedule::command('content:research-topics --ensure=7')
    ->dailyAt('02:00')
    ->timezone('Europe/Malta')
    ->runInBackground()
    ->withoutOverlapping(30);

Schedule::command('content:generate')
    ->dailyAt('03:00')
    ->timezone('Europe/Malta')
    ->runInBackground()
    ->withoutOverlapping(30);

Schedule::command('content:publish-due')->hourly();
