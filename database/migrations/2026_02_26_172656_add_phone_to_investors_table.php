<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
