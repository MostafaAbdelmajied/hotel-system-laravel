<?php

use App\Enums\ReservationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservations')) {
            return;
        }

        if (
            Schema::hasColumn('reservations', 'check_in') &&
            Schema::hasColumn('reservations', 'check_out') &&
            Schema::hasColumn('reservations', 'status')
        ) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'check_in')) {
                $table->date('check_in')->nullable()->after('accompany_number');
            }

            if (! Schema::hasColumn('reservations', 'check_out')) {
                $table->date('check_out')->nullable()->after('check_in');
            }

            if (! Schema::hasColumn('reservations', 'status')) {
                $table->enum('status', array_column(ReservationStatus::cases(), 'value'))
                    ->default(ReservationStatus::PENDING->value)
                    ->after('paid_price');
            }
        });
    }

    public function down(): void {}
};
