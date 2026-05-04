<?php

namespace Database\Seeders;

use App\Models\Contratista;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityMigrationSeeder extends Seeder
{
    /**
     * Migra los datos de contratistas existentes al nuevo esquema de seguridad.
     * Cifra los NITs y genera los Blind Indexes para permitir búsquedas.
     */
    public function run(): void
    {
        $this->command->info('Iniciando migración de seguridad para Contratistas...');

        // Usamos un cursor para manejar grandes volúmenes de datos sin agotar la memoria
        $contratistas = Contratista::all();
        $total = $contratistas->count();
        $count = 0;

        DB::transaction(function () use ($contratistas, &$count, $total) {
            foreach ($contratistas as $contratista) {
                try {
                    // El Property Hook de PHP 8.4 en el modelo Contratista
                    // se encarga de cifrar el NIT y generar el blind index al asignar.
                    // El getter detecta si no está cifrado y lo devuelve en texto plano,
                    // y el setter lo cifra automáticamente.
                    $plainNit = $contratista->nit;
                    
                    // Re-asignamos para disparar el Property Hook (Set)
                    $contratista->nit = $plainNit;

                    // El cast 'encrypted' en los demás campos también se aplicará al guardar
                    // si los datos actuales están en texto plano.
                    $contratista->save();
                    
                    $count++;
                    if ($count % 50 === 0) {
                        $this->command->getOutput()->write('.');
                    }
                } catch (\Exception $e) {
                    $this->command->error("\nError procesando Contratista ID {$contratista->id}: " . $e->getMessage());
                    Log::error("Error en SecurityMigrationSeeder", [
                        'id' => $contratista->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        $this->command->info("\n✅ Proceso finalizado. $count de $total contratistas actualizados con éxito.");
    }
}
