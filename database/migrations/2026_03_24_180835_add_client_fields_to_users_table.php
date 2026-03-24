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
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->default('Unknown')->after('email');
            $table->enum('gender', ['male', 'female'])->nullable()->after('country');
            $table->string('avatar')->nullable()->after('gender');
            $table->enum('status', ['pending', 'approved'])->default('pending')->after('avatar');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['country', 'gender', 'avatar', 'status', 'approved_by', 'approved_at']);
        });
    }
};
