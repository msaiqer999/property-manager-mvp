<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:mark-overdue')->dailyAt('01:00');
Schedule::command('contracts:expire')->dailyAt('00:30');
