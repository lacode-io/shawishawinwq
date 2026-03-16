<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('card_number')->nullable()->index()->after('notes');
            $table->string('card_code')->nullable()->after('card_number');
            $table->text('internal_notes')->nullable()->after('card_code');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['card_number']);
            $table->dropColumn(['card_number', 'card_code', 'internal_notes']);
        });
    }
};
