<?php

namespace App\Http\Controllers;

use App\Models\BloqueWorkflow;
use App\Models\Contrato;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403);
        }
        $roles = Role::orderBy('created_at', 'desc')->get();

        return view('configuracion.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403);
        }
        $bloques = BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();

        return view('configuracion.roles.create', compact('bloques'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:255',
            'permisos_matrix' => 'required|array',
            'ver_solo_asignados' => 'nullable|boolean',
            'ver_solo_bloques_con_asignacion' => 'nullable|boolean',
            'receptor_automatico_bloque_6' => 'nullable|boolean',
            'bloques_permitidos' => 'nullable|array',
            'responsables_bloque' => 'nullable|array',
        ]);

        // Logic to Map Matrix -> System Permissions
        $matrix = $request->input('permisos_matrix');
        $systemPermissions = $this->mapMatrixToSystemPermissions($matrix);

        // Add additional restrictions
        if ($request->boolean('ver_solo_asignados')) {
            $systemPermissions['ver_solo_asignados'] = true;
        }
        if ($request->boolean('ver_solo_bloques_con_asignacion')) {
            $systemPermissions['ver_solo_bloques_con_asignacion'] = true;
        }
        if ($request->boolean('receptor_automatico_bloque_6')) {
            $systemPermissions['receptor_automatico_bloque_6'] = true;
        }
        if ($request->has('acceder_notificaciones')) {
            $systemPermissions['acceder_notificaciones'] = $request->boolean('acceder_notificaciones');
        }
        if ($request->boolean('mover_todo_workflow')) {
            $systemPermissions['mover_todo_workflow'] = true;
        }

        // Handle Blocks
        if ($request->has('bloques_all')) {
            $systemPermissions['bloques_permitidos'] = true;
        } else {
            $systemPermissions['bloques_permitidos'] = $request->input('bloques_permitidos', []);
        }

        // Handle Responsibles por Bloque
        $systemPermissions['responsables_bloque'] = $request->input('responsables_bloque', []);

        // Handle Responsibilities (Legacy removed, using dynamic blocks)

        $role = Role::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'],
            'tipo' => 'PERSONALIZADO',
            'es_activo' => true,
        ]);

        $this->syncRolePermissions($role, $systemPermissions);

        return redirect()->route('configuracion.roles.index')->with('success', 'Rol personalizado creado exitosamente.');
    }

    /**
     * Map the frontend matrix keys to the backend permission keys.
     */
    private function mapMatrixToSystemPermissions($matrix)
    {
        $permissions = [
            // Default Deny
            'contratos_ver' => false,
            'contratos_editar' => false,
            'cuentas_ver' => false,
            'cuentas_editar' => false,
            'usuarios_gestionar' => false,
            'configuracion_sistema' => false,
            'es_admin' => false,
            'acceder_dashboard' => false,
            'acceder_consolidado' => false,
            'acceder_workflow' => false,
            'editar_workflow' => false,
            'editar_dashboard' => false,
            'acceder_reportes' => false,
            'reportes_exportar' => false,
            'logs_ver' => false,
            'ver_configuracion' => false,
            'editar_configuracion' => false,
            'ver_analitica' => false,
            'ver_seguimiento_secop' => false,
            'crear_seguimiento_secop' => false,
            'editar_seguimiento_secop' => false,
            'especiales_seguimiento_secop' => false,
            'ver_solo_asignados' => false,
            'ver_solo_bloques_con_asignacion' => false,
            'receptor_automatico_bloque_6' => false,
            'acceder_notificaciones' => true,
        ];

        // 1. Dashboard Module
        if (! empty($matrix['dashboard']['view'])) {
            $permissions['acceder_dashboard'] = true; // Gestión
        }
        if (! empty($matrix['dashboard']['readonly'])) {
            $permissions['acceder_consolidado'] = true; // Vista Consolidada
        }
        // If edit is unchecked, they can't manage dashboard
        if (empty($matrix['dashboard']['edit'])) {
            $permissions['editar_dashboard'] = false;
        } else {
            $permissions['editar_dashboard'] = true;
        }

        // 2. Users Module
        if (! empty($matrix['users']['view'])) {
            // Basic view? System currently only has "usuarios_gestionar" which is full admin.
            // For now, if they check 'edit/create/delete', we give them manage.
            if (! empty($matrix['users']['create']) || ! empty($matrix['users']['edit']) || ! empty($matrix['users']['delete'])) {
                $permissions['usuarios_gestionar'] = true;
            }
            if (! empty($matrix['users']['admin'])) {
                $permissions['es_admin'] = true;
            }
        }

        // 3-4 UNIFIED: Contracts and Accounts (Expedientes)
        if (! empty($matrix['seguimiento']['view'])) {
            $permissions['contratos_ver'] = true;
            $permissions['cuentas_ver'] = true;
        }
        if (! empty($matrix['seguimiento']['edit'])) {
            $permissions['contratos_editar'] = true;
            $permissions['cuentas_editar'] = true;
        }

        // 9. Seguimiento SECOP (SIA OBSERVA)
        if (! empty($matrix['seguimiento_secop']['view'])) {
            $permissions['ver_seguimiento_secop'] = true;
        }
        if (! empty($matrix['seguimiento_secop']['create'])) {
            $permissions['crear_seguimiento_secop'] = true;
        }
        if (! empty($matrix['seguimiento_secop']['edit'])) {
            $permissions['editar_seguimiento_secop'] = true;
        }
        if (! empty($matrix['seguimiento_secop']['especiales'])) {
            $permissions['especiales_seguimiento_secop'] = true;
        }

        // 5. Workflow Module
        if (! empty($matrix['workflow']['view'])) {
            $permissions['acceder_workflow'] = true;
        }
        if (! empty($matrix['workflow']['edit'])) {
            $permissions['editar_workflow'] = true;
        }

        // 6. Reports
        if (! empty($matrix['reports']['view'])) {
            $permissions['acceder_reportes'] = true;
        }
        if (! empty($matrix['reports']['export'])) {
            $permissions['reportes_exportar'] = true;
        }

        // 7. Configuration
        if (! empty($matrix['config']['view'])) {
            $permissions['ver_configuracion'] = true;
        }
        if (! empty($matrix['config']['edit'])) {
            $permissions['editar_configuracion'] = true;
        }

        // 8. Analytics
        if (! empty($matrix['analitica']['view'])) {
            $permissions['ver_analitica'] = true;
        }

        // 9 is now handled in 3-4-9 UNIFIED

        return $permissions;
    }

    /**
     * Show the form for editing a role.
     */
    public function edit($id)
    {
        /** @var Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403);
        }

        $role = Role::findOrFail($id);
        $bloques = BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();

        return view('configuracion.roles.edit', compact('role', 'bloques'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            /** @var Usuario $user */
            $user = Auth::user();
            if (! $user->isAdmin()) {
                abort(403);
            }

            $role = Role::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'required|string|max:50|unique:roles,nombre,' . $id,
                'descripcion' => 'nullable|string|max:255',
                'permisos_matrix' => 'required|array',
                'ver_solo_asignados' => 'nullable|boolean',
                'ver_solo_bloques_con_asignacion' => 'nullable|boolean',
                'receptor_automatico_bloque_6' => 'nullable|boolean',
                'bloques_permitidos' => 'nullable|array',
                'responsables_bloque' => 'nullable|array',
            ]);

            // Logic to Map Matrix -> System Permissions
            $systemPermissions = $role->lista_permisos; // Start with existing permissions

            // 1. Matriz de Privilegios
            if ($request->has('permisos_matrix')) {
                $newMatrixPerms = $this->mapMatrixToSystemPermissions($request->input('permisos_matrix'));
                foreach ($newMatrixPerms as $k => $v) {
                    $systemPermissions[$k] = $v;
                }
            } elseif ($request->has('_update_matrix')) {
                // User unchecked everything in the matrix
                $emptyMatrix = $this->mapMatrixToSystemPermissions([]);
                foreach ($emptyMatrix as $k => $v) {
                    $systemPermissions[$k] = $v;
                }
            }

            // 2. Restricciones de Datos
            if ($request->has('_update_restricciones') || $request->has('ver_solo_asignados')) {
                $systemPermissions['ver_solo_asignados'] = $request->boolean('ver_solo_asignados');
            }
            if ($request->has('_update_restricciones') || $request->has('ver_solo_bloques_con_asignacion')) {
                $systemPermissions['ver_solo_bloques_con_asignacion'] = $request->boolean('ver_solo_bloques_con_asignacion');
            }
            if ($request->has('_update_restricciones') || $request->has('receptor_automatico_bloque_6')) {
                $systemPermissions['receptor_automatico_bloque_6'] = $request->boolean('receptor_automatico_bloque_6');
            }
            if ($request->has('_update_restricciones') || $request->has('acceder_notificaciones')) {
                $systemPermissions['acceder_notificaciones'] = $request->boolean('acceder_notificaciones');
            }
            if ($request->has('_update_restricciones') || $request->has('mover_todo_workflow')) {
                $systemPermissions['mover_todo_workflow'] = $request->boolean('mover_todo_workflow');
            }

            // 3. Workflow
            if ($request->has('bloques_all')) {
                $systemPermissions['bloques_permitidos'] = true;
            } elseif ($request->has('bloques_permitidos')) {
                $systemPermissions['bloques_permitidos'] = $request->input('bloques_permitidos', []);
            } elseif ($request->has('_update_workflow')) {
                $systemPermissions['bloques_permitidos'] = [];
            }

            // 4. Responsabilidades
            if ($request->has('responsables_bloque')) {
                $systemPermissions['responsables_bloque'] = $request->input('responsables_bloque', []);
            } elseif ($request->has('_update_responsabilidades')) {
                $systemPermissions['responsables_bloque'] = [];
            }

            // Handle Responsibilities (Legacy removed, using dynamic blocks)

            $role->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
            ]);

            $this->syncRolePermissions($role, $systemPermissions);

            return redirect()->route('configuracion.roles.index')->with('success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            Contrato::logException($e, 'roles', ['operacion' => 'update', 'id' => $id]);
            return back()->withInput()->withErrors(['error' => 'Error inesperado al actualizar el rol: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle the active status of a role.
     */
    public function toggleStatus($id)
    {
        try {
            /** @var Usuario $user */
            $user = Auth::user();
            if (! $user->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            $role = Role::findOrFail($id);

            // Prevent deactivating roles that have active users
            if ($role->es_activo) {
                $usuariosActivos = $role->usuarios()->where('es_activo', true)->count();
                if ($usuariosActivos > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede desactivar este rol porque tiene {$usuariosActivos} usuario(s) activo(s) asignado(s).",
                    ], 400);
                }
            }

            $role->es_activo = ! $role->es_activo;
            $role->save();

            $status = $role->es_activo ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'message' => "Rol {$status} exitosamente.",
                'es_activo' => $role->es_activo,
            ]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'roles', ['operacion' => 'toggleStatus', 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function syncRolePermissions(Role $role, array $systemPermissions)
    {
        $permisoIds = [];

        foreach ($systemPermissions as $slug => $value) {
            if ($slug === 'bloques_permitidos') {
                if ($value === true) {
                    $slugName = 'acceso_bloque_all';
                    $permiso = Permiso::firstOrCreate(['slug' => $slugName], ['nombre' => 'Acceso Todo Bloque', 'modulo' => 'Bloques']);
                    $permisoIds[] = $permiso->id;
                } elseif (is_array($value)) {
                    foreach ($value as $bloqueCod) {
                        $slugName = 'acceso_bloque_' . $bloqueCod;
                        $permiso = Permiso::firstOrCreate(['slug' => $slugName], ['nombre' => 'Bloque ' . $bloqueCod, 'modulo' => 'Bloques']);
                        $permisoIds[] = $permiso->id;
                    }
                }
            } elseif ($slug === 'responsables_bloque') {
                if (is_array($value)) {
                    foreach ($value as $bloqueCod) {
                        $slugName = 'responsable_bloque_' . $bloqueCod;
                        $permiso = Permiso::firstOrCreate(['slug' => $slugName], ['nombre' => 'Responsable Bloque ' . $bloqueCod, 'modulo' => 'Responsabilidades']);
                        $permisoIds[] = $permiso->id;
                    }
                }
            } elseif ($value === true) {
                // Auto create permission if missing for backward compatibility with the dynamic matrix
                $permiso = Permiso::firstOrCreate(['slug' => $slug], [
                    'nombre' => ucwords(str_replace('_', ' ', $slug)),
                    'modulo' => 'General'
                ]);
                $permisoIds[] = $permiso->id;
            }
        }

        $role->permisos()->sync($permisoIds);
    }
}
