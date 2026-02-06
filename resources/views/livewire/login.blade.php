<div class="login-page">
    <div class="login-card">
        <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">Iniciar Sesión</h2>
        
        <form wire:submit="Login">
            <div class="form-group">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" id="usuario" wire:model="usuario" class="form-input" required>
                @error('usuario') <span style="color: red; font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="contrasenia" class="form-label">Contraseña</label>
                <input type="password" id="contrasenia" wire:model="contrasenia" class="form-input" required>
                @error('contrasenia') <span style="color: red; font-size: 0.875rem;">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="btn-primary">Ingresar</button>
        </form>
    </div>
</div>