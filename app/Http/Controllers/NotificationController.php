<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Services\StagnationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $stagnationService;

    public function __construct(\App\Services\StagnationService $stagnationService)
    {
        $this->stagnationService = $stagnationService;
    }

    public function getLatest(Request $request)
    {
        // EJECUCIÓN MANUAL: Verificar estancamiento antes de devolver las alertas
        $this->stagnationService->checkAll();

        $usuario = Auth::user();
        $alertas = Alerta::where('usuario_destino_id', $usuario->id)
            ->where('leida', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $alertas->count(),
            'alertas' => $alertas
        ]);
    }

    public function markAsRead($id)
    {
        $alerta = Alerta::findOrFail($id);
        
        if ($alerta->usuario_destino_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $alerta->update(['leida' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Alerta::where('usuario_destino_id', Auth::id())
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['success' => true]);
    }
}
