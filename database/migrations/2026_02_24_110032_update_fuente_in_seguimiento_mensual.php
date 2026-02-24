<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seguimiento_mensual', function (Blueprint $table) {
            DB::statement("ALTER TABLE seguimiento_mensual MODIFY COLUMN fuente ENUM('SECOP', 'SIA', 'REP') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seguimiento_mensual', function (Blueprint $table) {
            DB::statement("ALTER TABLE seguimiento_mensual MODIFY COLUMN fuente ENUM('SECOP', 'SIA') NOT NULL");
        });
    }
};
