<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('telescope:prune', [
    '--hours' => (int) config('telescope.prune_hours'),
])->dailyAt('02:00')->withoutOverlapping();
