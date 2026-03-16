<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('payment_due_date')->nullable()->after('delivery_date')->comment('تاريخ التسديد');
            $table->unsignedBigInteger('product_price_usd')->nullable()->after('product_sale_total')->comment('السعر بالدولار');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['payment_due_date', 'product_price_usd']);
        });
    }
};
