<?php

namespace App\Livewire\Cuentas;

use Livewire\Component;
use Livewire\WithFileUploads; // Imprescindible para manejar archivos
use App\Imports\CuentasImport;
use Maatwebsite\Excel\Facades\Excel;

class Importador extends Component
{
    use WithFileUploads;

    public $archivo; // Esta variable se vincula con wire:model="archivo"
    public $mensaje = '';
    public $tipoMensaje = ''; // 'success', 'error', 'warning'
    public $resumenImportacion = null;
    public $encabezados = [];

    /**
     * AQUÍ VA TU FUNCIÓN
     */
    public function importar()
    {
        // Prevenir múltiples clics
        if (!empty($this->mensaje)) {
            return;
        }

        $this->validate([
            'archivo' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        try {
            $import = new CuentasImport();
            Excel::import($import, $this->archivo->getRealPath());
            $resumen = $import->getResumen();
            $this->resumenImportacion = $resumen;
            $this->encabezados = $import->encabezadosEncontrados ?? [];

            // Cerrar modal y refrescar tabla si hubo al menos un registro exitoso
            if ($resumen['exitosas'] > 0) {
                $this->tipoMensaje = $resumen['fallidas'] === 0 ? 'success' : 'warning';
                $this->mensaje = $resumen['fallidas'] === 0
                    ? "✅ ¡IMPORTACIÓN EXITOSA! Se cargaron correctamente {$resumen['exitosas']} de {$resumen['total']} registros."
                    : "⚠️ IMPORTACIÓN PARCIAL: Se cargaron {$resumen['exitosas']} de {$resumen['total']} registros. {$resumen['fallidas']} registros fallaron.";
                    $this->dispatch('cuentas-actualizadas');
                $this->dispatchBrowserEvent('close-modal-import');
            } else if ($resumen['exitosas'] === 0 && $resumen['fallidas'] > 0) {
                $this->tipoMensaje = 'error';
                $this->mensaje = "❌ Error: No se pudo importar ningún registro. {$resumen['fallidas']} registros fallaron.";
            } else {
                $this->tipoMensaje = 'error';
                $this->mensaje = '❌ Error: No se procesaron registros.';
            }

            $this->archivo = null;
        } catch (\Exception $e) {
            $this->tipoMensaje = 'error';
            $this->mensaje = '❌ Error durante la importación: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.cuentas.importador');
    }
}