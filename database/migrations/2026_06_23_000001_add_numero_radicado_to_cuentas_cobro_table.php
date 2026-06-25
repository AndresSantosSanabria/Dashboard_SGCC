<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (! Schema::hasColumn('cuentas_cobro', 'numero_radicado')) {
                $table->string('numero_radicado', 80)->nullable()->unique()->after('numero_cuenta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            if (Schema::hasColumn('cuentas_cobro', 'numero_radicado')) {
                $table->dropUnique(['numero_radicado']);
                $table->dropColumn('numero_radicado');
            }
        });
    }
};
