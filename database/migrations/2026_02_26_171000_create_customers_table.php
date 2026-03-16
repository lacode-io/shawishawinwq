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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->string('product_type');
            $table->unsignedBigInteger('product_cost_price')->nullable()->comment('راس المال - بالدينار');
            $table->unsignedBigInteger('product_sale_total')->comment('السعر الاجمالي - بالدينار');
            $table->date('delivery_date');
            $table->unsignedTinyInteger('duration_months');
            $table->unsignedBigInteger('monthly_installment_amount')->comment('القسط الشهري - بالدينار');
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
        Schema::dropIfExists('customers');
    }
};
