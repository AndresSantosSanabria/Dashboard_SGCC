<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Role;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnaliticaTest extends TestCase
{
    use RefreshDatabase;

    public function test_analitica_abre_sin_error(): void
    {
        // Crear un rol admin con el permiso es_admin para poder acceder
        $role = Role::create(['nombre' => 'Admin Test', 'es_activo' => true, 'tipo' => 'SISTEMA']);
        $perm = Permiso::firstOrCreate(['slug' => 'es_admin'], ['nombre' => 'Admin', 'modulo' => 'SISTEMA']);
        $role->permisos()->sync([$perm->id]);

        /** @var Usuario $user */
        $user = Usuario::factory()->create(['rol_id' => $role->id, 'es_activo' => true]);

        $this->actingAs($user);
        $this->withoutExceptionHandling();
        $response = $this->get('/analitica');
        $response->assertStatus(200);
    }
}
