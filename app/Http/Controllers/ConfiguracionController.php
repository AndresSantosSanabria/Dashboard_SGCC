<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConfiguracionController extends Controller
{
    /**
     * Display list of users (admin only)
     */
    public function index()
    {
        // Registrar lectura de configuración (Auditoría)
        Contrato::logManualAudit(null, 'READ', 'El usuario consultó la configuración de usuarios', 'usuarios');

        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        // Check if user is admin
        if (! $user->isAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $usuarios = Usuario::with('rol')->orderBy('created_at', 'desc')->get();

        return view('configuracion.index', compact('usuarios'));
    }

    /**
     * Show create user form
     */
    public function create()
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $roles = Role::where('es_activo', true)->get();
        $bloques = \App\Models\BloqueWorkflow::all();

        return view('configuracion.form', compact('roles', 'bloques'));
    }

    /**
     * Store new user
     */
    public function store(Request $request)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }

        $validated = $request->validate([
            'primer_nombre' => 'required|string|max:50',
            'segundo_nombre' => 'nullable|string|max:50',
            'primer_apellido' => 'required|string|max:50',
            'segundo_apellido' => 'nullable|string|max:50',
            'user' => 'required|string|max:150|unique:usuarios,user',
            'password' => 'required|string|min:6|confirmed',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['es_activo'] = true;

        // Extract and format permissions
        $permisos = [];

        // Define all possible boolean permission keys
        $permissionKeys = [
            'acceder_dashboard',
            'editar_dashboard',
            'acceder_consolidado',
            'acceder_workflow',
            'editar_workflow',
            'es_admin',
            'ver_solo_asignados',
            'responsable_sap',
            'responsable_facturacion',
            'contratos_ver',
            'contratos_editar',
            'cuentas_ver',
            'cuentas_editar',
            'reportes_exportar',
        ];

        // Only save permissions that are explicitly TRUE
        // This way, FALSE permissions won't override role permissions
        foreach ($permissionKeys as $key) {
            if (isset($request->input('permisos', [])[$key]) && $request->input('permisos')[$key] == '1') {
                $permisos[$key] = true;
            }
        }

        // Handle blocks - only save if there are specific blocks selected
        if ($request->has('bloques_all')) {
            $permisos['bloques_permitidos'] = true;
        } else {
            $bloques = $request->input('bloques_permitidos', []);
            if (! empty($bloques)) {
                $permisos['bloques_permitidos'] = $bloques;
            }
        }

        // IMPORTANT: If the role is NOT personalized, clear individual permissions
        $role = Role::find($request->rol_id);
        $isPersonalizado = $role && str_contains(strtolower($role->nombre), 'personalizado');

        try {
            DB::beginTransaction();

            $usuario = new Usuario;
            $usuario->fill($validated);
            $usuario->save();

            // Sync permissions only if personalized
            if ($isPersonalizado && !empty($permisos)) {
                $permisoIds = \App\Models\Permiso::whereIn('slug', array_keys($permisos))->pluck('id')->toArray();

                // Handle special case for blocks
                if (isset($permisos['bloques_permitidos'])) {
                    if ($permisos['bloques_permitidos'] === true) {
                        $allBlockPerm = \App\Models\Permiso::where('slug', 'acceso_bloque_all')->first();
                        if ($allBlockPerm) $permisoIds[] = $allBlockPerm->id;
                    } else {
                        foreach ($permisos['bloques_permitidos'] as $codigo) {
                            $p = \App\Models\Permiso::firstOrCreate([
                                'slug' => "acceso_bloque_$codigo",
                                'nombre' => "Acceso a Bloque $codigo",
                                'modulo' => 'Bloques'
                            ]);
                            $permisoIds[] = $p->id;
                        }
                    }
                }

                $usuario->individualPermissions()->sync($permisoIds);
            }

            DB::commit();

            return redirect()->route('configuracion.index')
                ->with('success', 'Usuario creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Contrato::logException($e, 'usuarios', $request->all());

            return redirect()->back()->withInput()->with('error', 'Error al crear usuario: ' . $e->getMessage());
        }
    }

    /**
     * Show edit user form
     */
    public function edit($id)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $usuario = Usuario::with('rol')->findOrFail($id);
        $roles = Role::where('es_activo', true)->get();
        $bloques = \App\Models\BloqueWorkflow::all();

        return view('configuracion.form', compact('usuario', 'roles', 'bloques'));
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }

        $usuario = Usuario::findOrFail($id);

        $validated = $request->validate([
            'primer_nombre' => 'required|string|max:50',
            'segundo_nombre' => 'nullable|string|max:50',
            'primer_apellido' => 'required|string|max:50',
            'segundo_apellido' => 'nullable|string|max:50',
            'user' => 'required|string|max:150|unique:usuarios,user,' . $id,
            'password' => 'nullable|string|min:6|confirmed',
            'rol_id' => 'required|exists:roles,id',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Extract and format permissions
        $permisos = [];

        // Define all possible boolean permission keys
        $permissionKeys = [
            'acceder_dashboard',
            'editar_dashboard',
            'acceder_consolidado',
            'acceder_workflow',
            'editar_workflow',
            'es_admin',
            'ver_solo_asignados',
            'responsable_sap',
            'responsable_facturacion',
            'contratos_ver',
            'contratos_editar',
            'cuentas_ver',
            'cuentas_editar',
            'reportes_exportar',
        ];

        // Only save permissions that are explicitly TRUE
        // This way, FALSE permissions won't override role permissions
        foreach ($permissionKeys as $key) {
            if (isset($request->input('permisos', [])[$key]) && $request->input('permisos')[$key] == '1') {
                $permisos[$key] = true;
            }
        }

        // Handle blocks - only save if there are specific blocks selected
        if ($request->has('bloques_all')) {
            $permisos['bloques_permitidos'] = true;
        } else {
            $bloques = $request->input('bloques_permitidos', []);
            if (! empty($bloques)) {
                $permisos['bloques_permitidos'] = $bloques;
            }
        }

        // IMPORTANT: If the role is NOT personalized, clear individual permissions
        // to ensure the user inherits everything from the role without overrides.
        // We find the role name to check for "personalized" keyword.
        $role = Role::find($request->rol_id);
        $isPersonalizado = $role && str_contains(strtolower($role->nombre), 'personalizado');

        try {
            DB::beginTransaction();

            $usuario->fill($validated);
            $usuario->save();

            // Update permissions only if personalized
            if ($isPersonalizado && !empty($permisos)) {
                $permisoIds = \App\Models\Permiso::whereIn('slug', array_keys($permisos))->pluck('id')->toArray();

                // Handle special case for blocks
                if (isset($permisos['bloques_permitidos'])) {
                    if ($permisos['bloques_permitidos'] === true) {
                        $allBlockPerm = \App\Models\Permiso::where('slug', 'acceso_bloque_all')->first();
                        if ($allBlockPerm) $permisoIds[] = $allBlockPerm->id;
                    } else {
                        foreach ($permisos['bloques_permitidos'] as $codigo) {
                            $p = \App\Models\Permiso::firstOrCreate([
                                'slug' => "acceso_bloque_$codigo",
                                'nombre' => "Acceso a Bloque $codigo",
                                'modulo' => 'Bloques'
                            ]);
                            $permisoIds[] = $p->id;
                        }
                    }
                }

                $usuario->individualPermissions()->sync($permisoIds);
            } else {
                // If not personalized, clear individual permissions
                $usuario->individualPermissions()->detach();
            }

            DB::commit();

            return redirect()->route('configuracion.index')
                ->with('success', 'Usuario actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Contrato::logException($e, 'usuarios', $request->all());

            return redirect()->back()->withInput()->with('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus($id)
    {
        /** @var \App\Models\Usuario $user */
        $user = Auth::user();
        if (! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $usuario = Usuario::findOrFail($id);

        // Prevent deactivating yourself
        if ($usuario->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes desactivar tu propia cuenta',
            ], 400);
        }

        try {
            $usuario->es_activo = ! $usuario->es_activo;
            $usuario->fecha_inactivacion = $usuario->es_activo ? null : now();
            $usuario->save();

            return response()->json([
                'success' => true,
                'message' => $usuario->es_activo ? 'Usuario activado' : 'Usuario desactivado',
                'es_activo' => $usuario->es_activo,
            ]);
        } catch (\Exception $e) {
            Contrato::logException($e, 'usuarios', ['id' => $id]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
