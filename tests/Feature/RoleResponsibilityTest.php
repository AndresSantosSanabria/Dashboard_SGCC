<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleResponsibilityTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_can_create_role_with_responsibility_flags()
    {
        // 1. Create Admin Role and User
        $adminRole = \App\Models\Role::create([
            'nombre' => 'Administrador Test',
            'permisos' => ['es_admin' => true],
            'es_activo' => true,
            'tipo' => 'SISTEMA'
        ]);

        $user = \App\Models\Usuario::factory()->create([
            'rol_id' => $adminRole->id,
            'user' => 'admin_test',
            'password' => bcrypt('password')
        ]);

        $this->actingAs($user);

        // 2. Data to Submit
        $data = [
            'nombre' => 'Rol Test Responsable ' . rand(1000, 9999),
            'descripcion' => 'Descripcion de prueba',
            'permisos_matrix' => [
                'dashboard' => ['view' => 1]
            ],
            'es_responsable_sap' => 1,
            'es_responsable_facturacion' => 1,
        ];

        // 3. Post to store
        $response = $this->post(route('configuracion.roles.store'), $data);

        // 4. Assert Redirect
        $response->assertRedirect(route('configuracion.roles.index'));
        $response->assertSessionHas('success');

        // 5. Verify Database
        $this->assertDatabaseHas('roles', [
            'nombre' => $data['nombre'],
        ]);

        $role = \App\Models\Role::where('nombre', $data['nombre'])->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->tienePermiso('responsable_sap'));
        $this->assertTrue($role->tienePermiso('responsable_facturacion'));
    }

    public function test_can_update_role_responsibility_flags()
    {
        $adminRole = \App\Models\Role::create([
            'nombre' => 'Administrador Test 2',
            'permisos' => ['es_admin' => true],
            'es_activo' => true,
            'tipo' => 'SISTEMA'
        ]);

        $user = \App\Models\Usuario::factory()->create([
            'rol_id' => $adminRole->id,
            'user' => 'admin_test_2',
            'password' => bcrypt('password')
        ]);

        $this->actingAs($user);

        // Create initial role
        $role = \App\Models\Role::create([
            'nombre' => 'Rol Update Test ' . rand(1000, 9999),
            'descripcion' => 'Initial Description',
            'tipo' => 'PERSONALIZADO',
            'es_activo' => true,
            'permisos' => ['responsable_sap' => true, 'responsable_facturacion' => true]
        ]);

        // Update Data - Uncheck Facturacion
        $updateData = [
            'nombre' => $role->nombre,
            'descripcion' => 'Updated Description',
            'permisos_matrix' => [
                'dashboard' => ['view' => 1]
            ],
            'es_responsable_sap' => 1,
            // 'es_responsable_facturacion' => 0, // Not sending it means false/unchecked
        ];

        $response = $this->put(route('configuracion.roles.update', $role->id), $updateData);

        $response->assertRedirect(route('configuracion.roles.index'));

        $role->refresh();
        $this->assertTrue($role->tienePermiso('responsable_sap'));
        $this->assertFalse($role->tienePermiso('responsable_facturacion'));
    }
}
