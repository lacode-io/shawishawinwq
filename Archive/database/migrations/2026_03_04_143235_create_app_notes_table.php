<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notes', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('note');
            $table->string('title')->nullable();
            $table->longText('body');
            $table->json('tags')->nullable();
            $table->string('priority')->default('normal');
            $table->foreignId('related_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('related_investor_id')->nullable()->constrained('investors')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('updated_by_user_id')->constrained('users');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'priority', 'archived_at', 'pinned_at']);
            $table->fullText(['title', 'body']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notes');
    }
};
