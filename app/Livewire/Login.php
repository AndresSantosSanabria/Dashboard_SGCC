<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Usuario;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Login extends Component
{
    public $cedula = '';
    public $usuario = "";
    public $contrasenia = "";

    public function Login()
    {

        //Validar datos
        $this->validate([
            'usuario' => 'required|string|max:20',
            'contrasenia' => 'required|string|max:20',
        ]);

        //Buscar usuario
        $user = Usuario::where('usuario', $this->usuario)->first();

        if (auth()->attempt(['usuario' => $this->usuario, 'password' => $this->contrasenia])) {
            // Si es correcto, regeneramos la sesión por seguridad y redireccionamos
            session()->regenerate();
            return redirect()->intended('/dashboard');
        }
        // Si falla
        $this->addError('usuario', 'Credenciales incorrectas.');
    }
    public function render()
    {
        return view('livewire.login');
    }
}
