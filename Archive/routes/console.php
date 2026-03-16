<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('activitylog:clean --days=90')
    ->daily()
    ->at('02:00')
    ->timezone('Asia/Baghdad');

Schedule::command('queue:prune-batches --hours=48')
    ->daily()
    ->at('03:00');

Schedule::command('whatsapp:send-reminders')
    ->daily()
    ->at('09:00')
    ->timezone('Asia/Baghdad');
