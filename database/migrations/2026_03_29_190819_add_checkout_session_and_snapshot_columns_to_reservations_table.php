<?php

use App\Enums\ReservationStatus;
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

        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('reservations');

            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
                $table->integer('accompany_number');
                $table->date('check_in');
                $table->date('check_out');
                $table->unsignedBigInteger('paid_price');
                $table->enum('status', array_column(ReservationStatus::cases(), 'value'))->default(ReservationStatus::PENDING->value);
                $table->string('stripe_checkout_session_id')->nullable();
                $table->timestamps();
            });

            return;
        }

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
                $table->string('stripe_checkout_session_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('reservations');

            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
                $table->integer('accompany_number');
                $table->date('check_in');
                $table->date('check_out');
                $table->unsignedBigInteger('price');
                $table->enum('status', array_column(ReservationStatus::cases(), 'value'))->default(ReservationStatus::PENDING->value);
                $table->timestamps();
            });

            return;
        }

        try {
            DB::statement('ALTER TABLE reservations DROP INDEX reservations_stripe_checkout_session_id_unique');
        } catch (QueryException) {
        }

        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('stripe_checkout_session_id');
            $table->dropColumn('paid_price');
            $table->dropConstrainedForeignId('user_id');

            $table->foreignId('client_id')->after('id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('price')->after('check_out');
        });
    }
};
