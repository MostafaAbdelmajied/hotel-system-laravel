<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id')->unique()->nullable();
            $table->string('country')->default('Unknown')->after('email');
            $table->enum('gender', ['male', 'female'])->nullable()->after('country');
            $table->string('avatar')->nullable()->after('gender')->default('default.png');
            $table->enum('status', ['pending', 'approved'])->default('pending')->after('avatar');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('banned_at')->nullable();
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
