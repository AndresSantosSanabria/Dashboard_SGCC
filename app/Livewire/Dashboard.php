<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Usuario;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public function logout()
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }
    public function render()
    {
        return view('livewire.dashboard');
    }

}
