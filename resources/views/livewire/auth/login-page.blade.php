<section class="auth-card">
    <h2 style="margin: 0 0 0.35rem;">Iniciar sesión</h2>
    <p class="muted" style="margin: 0 0 1rem;">Accede con correo o número de empleado.</p>

    @if (session('status'))
        <div class="badge-soft" style="margin-bottom: 0.8rem;">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.perform') }}">
        @csrf
        <div style="margin-bottom: 0.7rem;">
            <label for="login">Correo o número de empleado</label>
            <input id="login" name="login" class="input" type="text" value="{{ old('login') }}" autocomplete="username" placeholder="tu@correo.com o 18680">
            <small class="muted">Puedes entrar con correo o con número de empleado.</small>
            @error('login') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="password">Contraseña</label>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <input id="login-password" name="password" class="input" type="password" autocomplete="current-password" placeholder="••••••••">
                <button type="button" class="btn btn-sm" data-toggle-password="login-password">Ver</button>
            </div>
            <small class="muted">Es tu contraseña de acceso al sistema.</small>
            @error('password') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <label style="display: inline-flex; align-items: center; gap: 0.4rem; margin-bottom: 0.9rem;">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Recordarme</span>
        </label>

        <button class="btn" type="submit" style="width: 100%;">Entrar</button>
    </form>

    <p style="text-align: center; margin: 0.9rem 0 0; font-size: 0.92rem;">
        <a href="{{ route('manual.public') }}">Manual de integración (Bitrix24 y Botmaker)</a>
    </p>

    <div style="display: flex; justify-content: space-between; margin-top: 0.75rem; font-size: 0.92rem;">
        <a href="{{ route('register') }}">Crear cuenta</a>
        <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>
    </div>
</section>
<script>
    (function () {
        document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.getAttribute('data-toggle-password'));
                if (!input) return;
                input.type = input.type === 'password' ? 'text' : 'password';
                this.textContent = input.type === 'password' ? 'Ver' : 'Ocultar';
            });
        });
    })();
</script>
