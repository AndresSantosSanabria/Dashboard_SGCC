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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE seguimiento_mensual MODIFY COLUMN fuente ENUM('SECOP', 'SIA', 'REP') NOT NULL");
        } else {
            Schema::table('seguimiento_mensual', function (Blueprint $table) {
                $table->string('fuente')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE seguimiento_mensual MODIFY COLUMN fuente ENUM('SECOP', 'SIA') NOT NULL");
        } else {
            Schema::table('seguimiento_mensual', function (Blueprint $table) {
                $table->string('fuente')->change();
            });
        }
    }
};
