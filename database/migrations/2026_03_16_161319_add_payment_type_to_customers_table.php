<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('payment_type')->default('installment')->after('delivery_date')->comment('نوع التسديد: installment أو lump_sum');
            $table->unsignedSmallInteger('lump_sum_days')->nullable()->after('payment_type')->comment('عدد الأيام للدفعة الواحدة');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'lump_sum_days']);
        });
    }
};
