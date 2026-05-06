<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('activitylog:clean --days=90')
    ->daily()
    ->at('02:00')
    ->timezone('Asia/Baghdad');

Schedule::command('queue:prune-batches --hours=48')
    ->daily()
    ->at('03:00');

// تخطيط اشعارات اليوم — يحسب من يستحق إشعار اليوم ويذبهن في scheduled_notifications.
// يلغي اي اشعارات معلقة من الأمس بحيث ما تنرسل متأخرة.
Schedule::command('whatsapp:plan-day')
    ->dailyAt('00:05')
    ->timezone('Asia/Baghdad');

// إرسال اشعار واحد كل دقيقة من اشعارات اليوم فقط.
// لو ما خلصنا اليوم، الاشعارات الباقية تنحط expired فجر باجر ويبدي يوم جديد نظيف.
Schedule::command('whatsapp:dispatch-next')
    ->everyMinute()
    ->between('08:00', '21:00')
    ->timezone('Asia/Baghdad')
    ->withoutOverlapping(2);
