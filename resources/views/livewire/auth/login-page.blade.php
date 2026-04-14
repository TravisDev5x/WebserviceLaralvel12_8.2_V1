<div class="card w-full">
    <header>
        <h2>Iniciar sesión</h2>
        <p>Ingresa tus datos para acceder a tu cuenta.</p>
    </header>

    <section>
        @if (session('status'))
            <div class="alert mb-3" role="status">
                <p class="m-0 text-sm">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login.perform') }}" class="form grid gap-6">
            @csrf

            <div class="grid gap-2">
                <label for="login">Correo o número de empleado</label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" placeholder="tu@correo.com o 18680">
                @error('login')
                    <p class="text-sm text-destructive m-0">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-2">
                <div class="flex items-center gap-2">
                    <label for="login-password">Contraseña</label>
                    <a href="{{ route('password.request') }}" class="ml-auto inline-block text-sm underline-offset-4 hover:underline">¿Olvidaste tu contraseña?</a>
                </div>
                <input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="••••••••">
                @error('password')
                    <p class="text-sm text-destructive m-0">{{ $message }}</p>
                @enderror
            </div>

            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                <span>Recordarme</span>
            </label>

            <footer class="flex flex-col items-center gap-2 p-0">
                <button class="btn w-full" type="submit">Entrar</button>
                <p class="mt-4 text-center text-sm">
                    ¿No tienes cuenta?
                    <a href="{{ route('register') }}" class="underline-offset-4 hover:underline">Crear cuenta</a>
                </p>
                <p class="text-center text-sm">
                    <a href="{{ route('manual.public') }}" class="underline-offset-4 hover:underline">Manual de integración (Bitrix24 y Botmaker)</a>
                </p>
            </footer>
        </form>
    </section>
</div>
