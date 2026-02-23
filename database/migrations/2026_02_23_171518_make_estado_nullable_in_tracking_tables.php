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
        Schema::table('seguimiento_mensual', function (Blueprint $table) {
            $table->string('estado')->nullable()->change();
        });

        Schema::table('seguimiento_requisitos', function (Blueprint $table) {
            $table->string('estado')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seguimiento_mensual', function (Blueprint $table) {
            $table->string('estado')->nullable(false)->change();
        });

        Schema::table('seguimiento_requisitos', function (Blueprint $table) {
            $table->string('estado')->nullable(false)->change();
        });
    }
};
