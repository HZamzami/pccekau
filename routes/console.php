<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('pccekau:daily-digest')->dailyAt('07:00');
