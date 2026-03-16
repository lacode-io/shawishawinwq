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
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->unsignedBigInteger('amount_invested')->comment('بالدينار');
            $table->unsignedTinyInteger('investment_months');
            $table->decimal('profit_percent_total', 5, 2)->comment('نسبة الربح الاجمالية');
            $table->date('start_date')->comment('تاريخ التسليم');
            $table->date('payout_due_date')->comment('تاريخ التسديد');
            $table->unsignedBigInteger('monthly_target_amount')->nullable()->comment('بالدينار');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('investors');
    }
};
