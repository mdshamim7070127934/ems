<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('email:refresh-oauth-tokens')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);

Schedule::command('email:refresh-mailbox-oauth-tokens')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30);

Schedule::command('email:sync-mailboxes')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
