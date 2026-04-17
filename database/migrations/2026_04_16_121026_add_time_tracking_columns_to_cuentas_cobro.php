<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Trazabilidad de Tiempos (Petición explícita del usuario)
        Schema::create('tasks_time_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_cobro_id')->constrained('cuentas_cobro')->cascadeOnDelete();
            $table->foreignId('estado_id')->constrained('estados_workflow');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duracion_segundos')->default(0);
            
            $table->string('tipo_cierre', 20)->nullable()->comment('MANUAL, HORARIO, TRANSICION');
            $table->timestamps();

            $table->index('cuenta_cobro_id');
            $table->index(['cuenta_cobro_id', 'end_time']); // Para encontrar el log activo rápido
        });

        // 2. Columnas de caché en la tabla principal para optimizar el Dashboard (Incremental)
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->integer('tiempo_total_segundos')->default(0)->after('observaciones');
            $table->timestamp('ultimo_inicio_conteo')->nullable()->after('tiempo_total_segundos');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_cobro', function (Blueprint $table) {
            $table->dropColumn(['tiempo_total_segundos', 'ultimo_inicio_conteo']);
        });
        Schema::dropIfExists('tasks_time_log');
    }
};
