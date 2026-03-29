<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservations')->delete();

        try {
            DB::statement('ALTER TABLE reservations DROP FOREIGN KEY reservations_client_id_foreign');
        } catch (QueryException) {
        }

        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'client_id')) {
                $table->dropColumn('client_id');
            }

            if (Schema::hasColumn('reservations', 'price')) {
                $table->dropColumn('price');
            }

            if (! Schema::hasColumn('reservations', 'user_id')) {
                $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
            }

            if (! Schema::hasColumn('reservations', 'paid_price')) {
                $table->unsignedBigInteger('paid_price')->after('check_out');
            }

            if (! Schema::hasColumn('reservations', 'stripe_checkout_session_id')) {
                $table->string('stripe_checkout_session_id')->nullable()->after('status');
                $table->unique('stripe_checkout_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropUnique('reservations_stripe_checkout_session_id_unique');
            $table->dropColumn('stripe_checkout_session_id');
            $table->dropColumn('paid_price');
            $table->dropConstrainedForeignId('user_id');

            $table->foreignId('client_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('price')->after('check_out');
        });
    }
};
