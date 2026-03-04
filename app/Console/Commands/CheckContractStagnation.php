<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StagnationService;

class CheckContractStagnation extends Command
{
    protected $signature = 'contracts:check-stagnation';
    protected $description = 'Verifica contratos estancados en un estado por más tiempo del permitido';

    protected $stagnationService;

    public function __construct(StagnationService $stagnationService)
    {
        parent::__construct();
        $this->stagnationService = $stagnationService;
    }

    public function handle()
    {
        $this->info("Iniciando verificación de estancamiento de contratos...");
        $this->stagnationService->checkAll();
        $this->info("Verificación completada. Se procesaron/actualizaron las alertas.");
    }
}
