<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finance_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closed_at');
            $table->text('notes')->nullable();
            $table->json('rules_applied')->nullable();
            $table->json('snapshot_data')->nullable()->comment('Calculated totals at closing time');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_closings');
    }
};
