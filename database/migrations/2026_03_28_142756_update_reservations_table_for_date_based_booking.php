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
        if (! Schema::hasTable('reservations')) {
            return;
        }

        $hasClientId = Schema::hasColumn('reservations', 'client_id');
        $hasUserId = Schema::hasColumn('reservations', 'user_id');
        $hasCheckIn = Schema::hasColumn('reservations', 'check_in');
        $hasCheckOut = Schema::hasColumn('reservations', 'check_out');

        if ($hasClientId && ! $hasUserId) {
            $this->dropForeignKeysForColumn('reservations', 'client_id');

            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('client_id', 'user_id');
            });
        }

        if (! $hasCheckIn || ! $hasCheckOut) {
            Schema::table('reservations', function (Blueprint $table) use ($hasCheckIn, $hasCheckOut) {
                if (! $hasCheckIn) {
                    $table->date('check_in');
                }

                if (! $hasCheckOut) {
                    $table->date('check_out');
                }
            });
        }

        if (! Schema::hasIndex('reservations', 'reservations_room_date_range_index')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->index(['room_id', 'check_in', 'check_out'], 'reservations_room_date_range_index');
            });
        }

        $hasUserId = Schema::hasColumn('reservations', 'user_id');
        $hasRoomId = Schema::hasColumn('reservations', 'room_id');

        if ($hasUserId && ! $this->hasForeignKeyOnColumn('reservations', 'user_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('user_id', 'reservations_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }

        if ($hasRoomId && ! $this->hasForeignKeyOnColumn('reservations', 'room_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('room_id', 'reservations_room_id_foreign')
                    ->references('id')
                    ->on('rooms')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('reservations')) {
            return;
        }

        if (Schema::hasIndex('reservations', 'reservations_room_date_range_index')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropIndex('reservations_room_date_range_index');
            });
        }

        $hasCheckIn = Schema::hasColumn('reservations', 'check_in');
        $hasCheckOut = Schema::hasColumn('reservations', 'check_out');

        if ($hasCheckIn || $hasCheckOut) {
            Schema::table('reservations', function (Blueprint $table) use ($hasCheckIn, $hasCheckOut) {
                if ($hasCheckOut) {
                    $table->dropColumn('check_out');
                }

                if ($hasCheckIn) {
                    $table->dropColumn('check_in');
                }
            });
        }

        $hasUserId = Schema::hasColumn('reservations', 'user_id');
        $hasClientId = Schema::hasColumn('reservations', 'client_id');

        if ($hasUserId && ! $hasClientId) {
            $this->dropForeignKeysForColumn('reservations', 'user_id');

            Schema::table('reservations', function (Blueprint $table) {
                $table->renameColumn('user_id', 'client_id');
            });
        }

        if (Schema::hasColumn('reservations', 'client_id') && ! $this->hasForeignKeyOnColumn('reservations', 'client_id')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->foreign('client_id', 'reservations_client_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Determine whether a table has a foreign key constraint on a given column.
     */
    private function hasForeignKeyOnColumn(string $table, string $column): bool
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop all foreign keys attached to a specific column.
     */
    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            if (! in_array($column, $foreignKey['columns'], true)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey['columns']);
            });
        }
    }
};
