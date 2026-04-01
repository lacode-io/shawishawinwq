<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Investor new fields
        Schema::table('investors', function (Blueprint $table) {
            $table->date('total_payout_date')->nullable()->after('payout_due_date');
            $table->integer('amount_usd')->nullable()->after('amount_invested');
        });

        // Settings extra capital
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('extra_capital')->default(0)->after('cash_capital');
        });

        // Expense custom type name
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('custom_type_name')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn(['total_payout_date', 'amount_usd']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('extra_capital');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('custom_type_name');
        });
    }
};
