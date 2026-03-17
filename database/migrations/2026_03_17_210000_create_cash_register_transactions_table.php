<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->string('description');
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_transactions');
    }
};
