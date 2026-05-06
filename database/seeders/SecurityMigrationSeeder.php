<?php

namespace Database\Seeders;

use App\Models\Contratista;
use Illuminate\Database\Seeder;
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

        $contratistas = Contratista::all();
        $total        = $contratistas->count();
        $count        = 0;
        $errors       = 0;

        $this->command->info("Total de contratistas a procesar: {$total}");

        foreach ($contratistas as $contratista) {
            try {
                // El getter del Property Hook detecta si el valor ya está cifrado;
                // si no lo está, devuelve el texto plano.
                $plainNit = $contratista->nit;

                // Re-asignamos para disparar el setter (cifra + blind index).
                $contratista->nit = $plainNit;

                // Los casts 'encrypted' del modelo cifran los demás campos al guardar.
                $contratista->save();

                $count++;
                if ($count % 50 === 0) {
                    $this->command->getOutput()->write('.');
                }
            } catch (\Exception $e) {
                $errors++;
                $this->command->error(
                    "\nError procesando Contratista ID {$contratista->id}: " . $e->getMessage()
                );
                Log::error('Error en SecurityMigrationSeeder', [
                    'id'    => $contratista->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->command->newLine();
        $this->command->info("Proceso finalizado. {$count} de {$total} actualizados con éxito.");

        if ($errors > 0) {
            $this->command->warn("{$errors} contratista(s) fallaron. Revisa el log para detalles.");
        }
    }
}
