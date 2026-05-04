<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BloqueWorkflow;
use App\Models\Contratista;
use App\Models\Contrato;
use App\Models\Supervisor;
use App\Models\CuentaCobro;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Collection;

class ImportService
{
    /**
     * PHP 8.4 Property Hook for allowed extensions.
     */
    public array $allowedExtensions {
        get => ['xlsx', 'xls', 'csv', 'xlsm'];
    }

    /**
     * Processes the Excel import.
     *
     * @param UploadedFile $file
     * @param Usuario $user
     * @return array{success: bool, message: string, errors: array}
     */
    public function importExcel(UploadedFile $file, Usuario $user): array
    {
        Log::info('=== IMPORTACIÓN INICIADA (Service) ===');
        Log::info('Archivo: ' . $file->getClientOriginalName());
        Log::info('Usuario: ' . $user->email);

        try {
            $rows = SimpleExcelReader::create($file->getRealPath(), $file->getClientOriginalExtension())->getRows();

            if ($rows->isEmpty()) {
                throw new \Exception('Archivo vacío o formato inválido');
            }

            $importCount = 0;
            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            $bloqueRad = BloqueWorkflow::where('codigo', 'REV1')->first();
            $estadoRad = $bloqueRad?->estadoInicial;

            foreach ($rows as $index => $row) {
                $filaActual = $index + 1;
                try {
                    // CSV Injection Protection & Normalization
                    $data = $this->processRowData($row);

                    $numContrato = strtoupper(trim($this->getCellValue($data, ['NUMERO DE CONTRATO', 'N° CONTRATO', 'CONTRATO'])));

                    if (empty($numContrato)) {
                        Log::warning("Fila $filaActual: No tiene NUMERO DE CONTRATO, saltando");
                        continue;
                    }

                    DB::transaction(function () use ($data, &$importCount, &$createdCount, &$updatedCount, $numContrato, $user) {
                        $this->processContractData($data, $numContrato, $user, $importCount, $createdCount, $updatedCount);
                    });

                } catch (\Throwable $e) {
                    $errorMsg = "Fila $filaActual: " . $e->getMessage();
                    $errors[] = $errorMsg;
                    Log::error($errorMsg);
                }
            }

            $summary = "Importación finalizada. Total: $importCount ($createdCount creados, $updatedCount actualizados). Errores: " . count($errors);
            Log::info($summary);

            return [
                'success' => true,
                'message' => $summary,
                'errors' => $errors
            ];

        } catch (\Throwable $e) {
            Log::error('ERROR GENERAL EN IMPORTACIÓN: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => []
            ];
        }
    }

    /**
     * Normalizes keys and sanitizes values for CSV Injection.
     */
    private function processRowData(array|Collection $row): array
    {
        $data = [];
        foreach ($row as $k => $v) {
            $cleanKey = strtoupper(trim(str_replace(["\n", "\r", "\t"], ' ', (string)$k)));
            
            // CSV Injection Protection: Prefix with single quote if it starts with risky characters
            $value = (string)$v;
            if (preg_match('/^[=\+\-@]/', $value)) {
                $value = "'" . $value;
            }
            
            $data[$cleanKey] = $value;
        }
        return $data;
    }

    private function getCellValue(array $data, string|array $keys, mixed $default = null): mixed
    {
        if (is_string($keys)) {
            return $data[$keys] ?? $default;
        }

        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return $data[$key];
            }
        }

        return $default;
    }

    private function processContractData(array $data, string $numContrato, Usuario $user, &$importCount, &$createdCount, &$updatedCount): void
    {
        // 1. Contratista
        $nit = strtoupper(trim($this->getCellValue($data, ['CEDULA', 'NIT', 'CÉDULA'], '0')));
        $contratista = Contratista::whereNit($nit)->first();
        $contratistaData = [
            'razon_social' => strtoupper(trim($this->getCellValue($data, 'CONTRATISTA', 'SIN NOMBRE'))),
            'tipo_persona' => (strlen($nit) > 10) ? 'JURIDICA' : 'NATURAL',
        ];

        if ($contratista) {
            $contratista->update($contratistaData);
        } else {
            $contratistaData['nit'] = $nit;
            $contratista = Contratista::create($contratistaData);
        }

        // 2. Supervisor
        $supervisorName = $this->getCellValue($data, 'SUPERVISOR', 'PENDIENTE');
        // Assuming splitFullName exists in a helper or trait, here we implement a simple version
        $parts = explode(' ', $supervisorName, 2);
        $supervisor = Supervisor::updateOrCreate(
            ['nombres' => $parts[0], 'apellidos' => $parts[1] ?? ''],
            ['cargo' => 'SUPERVISOR']
        );

        // 3. Contrato
        $contrato = Contrato::updateOrCreate(['numero_contrato' => $numContrato], [
            'contratista_id' => $contratista->id,
            'supervisor_id' => $supervisor->id,
            'monto_total' => $this->parseAmount($this->getCellValue($data, ['VALOR RP', 'VALOR CONTRATO'], 0)),
            'es_activo' => true,
        ]);

        // 4. Cuenta Cobro logic (Simplified for this example, but should follow existing patterns)
        $numeroCuenta = (string) $this->getCellValue($data, ['NUMERO DE CUENTA', 'N° CUENTA'], '1');
        
        $cuenta = CuentaCobro::updateOrCreate(
            ['contrato_id' => $contrato->id, 'numero_cuenta' => $numeroCuenta],
            [
                'responsable_actual_id' => $user->id,
                'finalizada' => strtoupper(trim($this->getCellValue($data, 'RADICADA EN HACIENDA', ''))) === 'SI',
                // ... other fields
            ]
        );

        if ($cuenta->wasRecentlyCreated) {
            $createdCount++;
        } else {
            $updatedCount++;
        }

        $importCount++;
    }

    private function parseAmount(mixed $value): float
    {
        if (is_numeric($value)) return (float) $value;
        return (float) preg_replace('/[^0-9.]/', '', (string)$value);
    }
}
