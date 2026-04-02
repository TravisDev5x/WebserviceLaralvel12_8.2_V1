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
            <input id="login" name="login" class="input" type="text" value="{{ old('login') }}" autocomplete="username">
            @error('login') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <div style="margin-bottom: 0.7rem;">
            <label for="password">Contraseña</label>
            <input id="password" name="password" class="input" type="password" autocomplete="current-password">
            @error('password') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </div>

        <label style="display: inline-flex; align-items: center; gap: 0.4rem; margin-bottom: 0.9rem;">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Recordarme</span>
        </label>

        <button class="btn" type="submit" style="width: 100%;">Entrar</button>
    </form>

    <div style="display: flex; justify-content: space-between; margin-top: 0.9rem; font-size: 0.92rem;">
        <a href="{{ route('register') }}">Crear cuenta</a>
        <a href="{{ route('password.request') }}">Olvidé mi contraseña</a>
    </div>
</section>
