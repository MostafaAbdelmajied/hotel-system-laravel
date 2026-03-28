<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! Schema::hasColumn('floors', 'managed_by')) {
            Schema::table('floors', function (Blueprint $table) {
                $table->unsignedBigInteger('managed_by')->nullable()->after('created_by');
            });
        }

        if ($driver !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE floors MODIFY managed_by BIGINT UNSIGNED NULL');

        $foreignKeyExists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'floors')
            ->where('CONSTRAINT_NAME', 'floors_managed_by_foreign')
            ->exists();

        if (! $foreignKeyExists) {
            Schema::table('floors', function (Blueprint $table) {
                $table->foreign('managed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        $foreignKeyExists = false;

        if ($driver === 'mysql') {
            $foreignKeyExists = DB::table('information_schema.TABLE_CONSTRAINTS')
                ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'floors')
                ->where('CONSTRAINT_NAME', 'floors_managed_by_foreign')
                ->exists();
        }

        Schema::table('floors', function (Blueprint $table) {
            if ($foreignKeyExists) {
                $table->dropForeign('floors_managed_by_foreign');
            }

            if (Schema::hasColumn('floors', 'managed_by')) {
                $table->dropColumn('managed_by');
            }
        });
    }
};
