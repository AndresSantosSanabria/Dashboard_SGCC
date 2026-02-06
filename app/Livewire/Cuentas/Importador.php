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

    /**
     * AQUÍ VA TU FUNCIÓN
     */
    public function importar()
    {
        $this->validate([
            'archivo' => 'required|mimes:xlsx,xls|max:10240'
        ]);

        try {
            // Se procesa el archivo usando la clase CuentasImport
            Excel::import(new CuentasImport, $this->archivo->getRealPath());
            
            $this->mensaje = 'Importación exitosa';
            $this->archivo = null;

            // Eventos para el frontend
            $this->dispatch('cuentas-actualizadas'); // Para refrescar la tabla de abajo
            $this->dispatch('close-modal-import');  // Para cerrar el modal de Alpine.js
            
        } catch (\Exception $e) {
            $this->mensaje = 'Error: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.cuentas.importador');
    }
}