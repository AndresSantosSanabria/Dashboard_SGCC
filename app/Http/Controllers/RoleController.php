<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\Usuario $user */
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
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403);
        }
        $bloques = \App\Models\BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();

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
            'bloques_permitidos' => 'nullable|array',
        ]);

        // Logic to Map Matrix -> System Permissions
        $matrix = $request->input('permisos_matrix');
        $systemPermissions = $this->mapMatrixToSystemPermissions($matrix);

        // Add additional restrictions
        if ($request->boolean('ver_solo_asignados')) {
            $systemPermissions['ver_solo_asignados'] = true;
        }

        // Handle Blocks
        if ($request->has('bloques_all')) {
            $systemPermissions['bloques_permitidos'] = true;
        } else {
            $systemPermissions['bloques_permitidos'] = $request->input('bloques_permitidos', []);
        }

        // Handle Responsibilities
        if ($request->boolean('es_responsable_sap')) {
            $systemPermissions['responsable_sap'] = true;
        }
        if ($request->boolean('es_responsable_facturacion')) {
            $systemPermissions['responsable_facturacion'] = true;
        }

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
            'responsable_sap' => false,
            'responsable_facturacion' => false,
            'editar_dashboard' => false,
            'reportes_exportar' => false,
            'logs_ver' => false,
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
        }

        // 3. Contracts Module
        if (! empty($matrix['contracts']['view'])) {
            $permissions['contratos_ver'] = true;
        }
        if (! empty($matrix['contracts']['edit'])) {
            $permissions['contratos_editar'] = true;
        }

        // 4. Accounts Module
        if (! empty($matrix['accounts']['view'])) {
            $permissions['cuentas_ver'] = true;
        }
        if (! empty($matrix['accounts']['edit'])) {
            $permissions['cuentas_editar'] = true;
        }

        // 5. Workflow Module
        if (! empty($matrix['workflow']['view'])) {
            $permissions['acceder_workflow'] = true;
        }
        if (! empty($matrix['workflow']['edit'])) {
            $permissions['editar_workflow'] = true;
        }

        // 6. Reports
        if (! empty($matrix['reports']['export'])) {
            $permissions['reportes_exportar'] = true;
        }

        return $permissions;
    }

    /**
     * Show the form for editing a role.
     */
    public function edit($id)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403);
        }

        $role = Role::findOrFail($id);
        $bloques = \App\Models\BloqueWorkflow::where('es_activo', true)->orderBy('orden')->get();

        return view('configuracion.roles.edit', compact('role', 'bloques'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            /** @var \App\Models\Usuario $user */
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
                'bloques_permitidos' => 'nullable|array',
            ]);

            // Logic to Map Matrix -> System Permissions
            $matrix = $request->input('permisos_matrix');
            $systemPermissions = $this->mapMatrixToSystemPermissions($matrix);

            // Add additional restrictions
            if ($request->boolean('ver_solo_asignados')) {
                $systemPermissions['ver_solo_asignados'] = true;
            }

            // Handle Blocks
            if ($request->has('bloques_all')) {
                $systemPermissions['bloques_permitidos'] = true;
            } else {
                $bloquesArray = $request->input('bloques_permitidos', []);
                $systemPermissions['bloques_permitidos'] = $bloquesArray;
            }

            // Handle Responsibilities
            if ($request->boolean('es_responsable_sap')) {
                $systemPermissions['responsable_sap'] = true;
            }
            if ($request->boolean('es_responsable_facturacion')) {
                $systemPermissions['responsable_facturacion'] = true;
            }

            $role->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'],
            ]);

            $this->syncRolePermissions($role, $systemPermissions);

            return redirect()->route('configuracion.roles.index')->with('success', 'Rol actualizado exitosamente.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error inesperado al actualizar el rol: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle the active status of a role.
     */
    public function toggleStatus($id)
    {
        try {
            /** @var \App\Models\Usuario $user */
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
                    $permiso = \App\Models\Permiso::firstOrCreate(['slug' => $slugName], ['nombre' => 'Acceso Todo Bloque', 'modulo' => 'Bloques']);
                    $permisoIds[] = $permiso->id;
                } elseif (is_array($value)) {
                    foreach ($value as $bloqueCod) {
                        $slugName = 'acceso_bloque_' . $bloqueCod;
                        $permiso = \App\Models\Permiso::firstOrCreate(['slug' => $slugName], ['nombre' => 'Bloque ' . $bloqueCod, 'modulo' => 'Bloques']);
                        $permisoIds[] = $permiso->id;
                    }
                }
            } elseif ($value === true) {
                // Auto create permission if missing for backward compatibility with the dynamic matrix
                $permiso = \App\Models\Permiso::firstOrCreate(['slug' => $slug], [
                    'nombre' => ucwords(str_replace('_', ' ', $slug)),
                    'modulo' => 'General'
                ]);
                $permisoIds[] = $permiso->id;
            }
        }

        $role->permisos()->sync($permisoIds);
    }
}
