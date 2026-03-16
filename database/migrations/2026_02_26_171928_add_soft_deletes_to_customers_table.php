<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes();
            $table->text('deletion_reason')->nullable();
            $table->foreignId('deletion_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deletion_approved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['deletion_approved_by']);
            $table->dropForeign(['deletion_requested_by']);
            $table->dropColumn(['deleted_at', 'deletion_reason', 'deletion_requested_by', 'deletion_approved_by']);
        });
    }
};
