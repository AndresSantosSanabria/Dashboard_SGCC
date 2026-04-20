<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TRAZABILIDAD DE TIEMPOS POR TAREA
     * 
     * Registra cada sesión de trabajo realizada sobre una cuenta de cobro en un 
     * estado específico. Permite una auditoría exacta de cuánto tiempo "activo" 
     * pasó cada cuenta en cada etapa del workflow.
     */
    public function up(): void
    {
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
            $table->index(['cuenta_cobro_id', 'end_time']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks_time_log');
    }
};
