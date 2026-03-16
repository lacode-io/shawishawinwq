<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_phone');
            $table->string('message_type')->index();
            $table->json('payload')->nullable();
            $table->string('provider');
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->nullableMorphs('notifiable');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['to_phone', 'message_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
