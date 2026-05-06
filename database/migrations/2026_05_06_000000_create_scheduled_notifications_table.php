<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->date('scheduled_for')->index();
            $table->string('message_type', 60);
            $table->nullableMorphs('notifiable');
            $table->string('to_phone');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->foreignId('notification_log_id')->nullable()->constrained('notification_logs')->nullOnDelete();
            $table->text('error')->nullable();
            $table->string('expired_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['scheduled_for', 'status']);
            $table->unique(
                ['notifiable_type', 'notifiable_id', 'message_type', 'scheduled_for'],
                'scheduled_notifications_unique_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
