<?php

use App\Http\Controllers\IntegrationManualController;
use App\Http\Controllers\IntegrationProbeController;
use App\Livewire\AccessControlManager;
use App\Livewire\AlertRuleManager;
use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\Auth\VerifyEmailPage;
use App\Livewire\AuthorizedTokenManager;
use App\Livewire\Bitrix24Settings;
use App\Livewire\BotmakerSettings;
use App\Livewire\EventFilterManager;
use App\Livewire\FailedWebhookList;
use App\Livewire\FieldMappingManager;
use App\Livewire\IntegrationManual;
use App\Livewire\IntegrationTestPanel;
use App\Livewire\MessageTemplateManager;
use App\Livewire\NotificationRuleManager;
use App\Livewire\ProfilePage;
use App\Livewire\RetrySettings;
use App\Livewire\SettingsHub;
use App\Livewire\UserManager;
use App\Livewire\WebhookDashboard;
use App\Livewire\WebhookLogDetail;
use App\Livewire\WebhookLogList;
use App\Livewire\WhatsappNumberManager;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::redirect('/', '/monitor');

Route::get('/manual', [IntegrationManualController::class, 'show'])->name('manual.public');
Route::get('/manual/descargar-pdf', [IntegrationManualController::class, 'downloadPdf'])->name('manual.pdf');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', LoginPage::class)->name('login');
    Route::get('/register', RegisterPage::class)->name('register');
    Route::get('/forgot-password', ForgotPasswordPage::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');

    Route::post('/login', function (Request $request) {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = trim((string) $validated['login']);
        $normalizedLogin = Str::lower($login);
        $password = (string) $validated['password'];
        $remember = (bool) ($validated['remember'] ?? false);
        $attemptKey = 'login:attempts:'.$request->ip().'|'.$normalizedLogin;
        $lockKey = 'login:lock:'.$request->ip().'|'.$normalizedLogin;

        if (RateLimiter::tooManyAttempts($lockKey, 1)) {
            $seconds = RateLimiter::availableIn($lockKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            return back()
                ->withErrors(['login' => "Demasiados intentos. Intente de nuevo en {$minutes} minutos."])
                ->onlyInput('login');
        }

        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? User::query()->where('email', strtolower($login))->first()
            : User::query()->where('employee_number', $login)->first();

        if ($user !== null && Hash::check($password, (string) $user->password)) {
            if (! $user->is_active) {
                return back()->withErrors(['login' => 'Tu usuario está desactivado.'])->onlyInput('login');
            }
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            RateLimiter::clear($attemptKey);
            RateLimiter::clear($lockKey);

            return redirect()->intended('/monitor');
        }

        RateLimiter::hit($attemptKey, 300);
        if (RateLimiter::attempts($attemptKey) >= 5) {
            RateLimiter::clear($attemptKey);
            RateLimiter::hit($lockKey, 900);

            return back()
                ->withErrors(['login' => 'Demasiados intentos. Intente de nuevo en 15 minutos.'])
                ->onlyInput('login');
        }

        return back()->withErrors(['login' => 'Credenciales inválidas.'])->onlyInput('login');
    })->name('login.perform');

    Route::post('/forgot-password', function (Request $request) {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
        ]);

        $login = trim((string) $validated['login']);
        $email = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? strtolower($login)
            : User::query()->where('employee_number', $login)->value('email');

        if (! is_string($email) || $email === '') {
            return back()->withErrors(['login' => 'No encontramos una cuenta asociada.']);
        }

        $status = Password::sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['login' => __($status)]);
    })->name('password.email');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', VerifyEmailPage::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect('/monitor');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// TODO: Restaurar middleware 'verified' en este grupo cuando el mailer esté configurado (verificación de correo obligatoria).
Route::middleware(['auth'])->group(function (): void {
    Route::get('/monitor/profile', ProfilePage::class)->name('profile.edit');
    Route::get('/monitor/manual', IntegrationManual::class)->middleware('permission:monitor.view')->name('manual.app');
    Route::get('/monitor', WebhookDashboard::class)->middleware('permission:monitor.view');
    Route::get('/monitor/logs', WebhookLogList::class)->middleware('permission:logs.view');
    Route::get('/monitor/logs/{webhookLogId}', WebhookLogDetail::class)->middleware('permission:logs.view');
    Route::get('/monitor/failed', FailedWebhookList::class)->middleware('permission:failed.view');
    Route::get('/monitor/settings', SettingsHub::class)->middleware('permission:settings.manage')->name('settings.hub');
    Route::get('/monitor/settings/botmaker', BotmakerSettings::class)->middleware('role:admin')->name('settings.botmaker');
    Route::get('/monitor/settings/bitrix24', Bitrix24Settings::class)->middleware('role:admin')->name('settings.bitrix24');
    Route::get('/monitor/settings/tokens', AuthorizedTokenManager::class)->middleware('role:admin')->name('monitor.tokens');
    Route::get('/monitor/settings/retry', RetrySettings::class)->middleware('role:admin')->name('settings.retry');
    Route::get('/monitor/settings/test', IntegrationTestPanel::class)->middleware('role:admin')->name('integration-tests.panel');
    Route::redirect('/monitor/integration-tests', '/monitor/settings/test');
    Route::redirect('/monitor/tokens', '/monitor/settings/tokens');
    Route::redirect('/monitor/field-mappings', '/monitor/mappings');
    Route::redirect('/monitor/notification-rules', '/monitor/notifications');
    Route::post('/monitor/integration-probes/bitrix-sample', [IntegrationProbeController::class, 'bitrixSample'])->middleware(['role:admin', 'throttle:15,60'])->name('integration-probes.bitrix-sample');
    Route::get('/monitor/integration-probes/connectivity', [IntegrationProbeController::class, 'connectivity'])->middleware(['role:admin', 'throttle:60,1'])->name('integration-probes.connectivity');
    Route::get('/monitor/mappings', FieldMappingManager::class)->middleware(['role.ops', 'permission:mappings.manage']);
    Route::get('/monitor/notifications', NotificationRuleManager::class)->middleware(['role.ops', 'permission:notifications.manage']);
    Route::get('/monitor/templates', MessageTemplateManager::class)->middleware(['role.ops', 'permission:templates.manage']);
    Route::get('/monitor/whatsapp-numbers', WhatsappNumberManager::class)->middleware(['role.ops', 'permission:whatsapp.manage']);
    Route::get('/monitor/event-filters', EventFilterManager::class)->middleware(['role.ops', 'permission:filters.manage']);
    Route::get('/monitor/alerts', AlertRuleManager::class)->middleware('permission:alerts.manage');
    Route::get('/monitor/users', UserManager::class)->middleware('permission:users.manage');
    Route::get('/monitor/access-control', AccessControlManager::class)->middleware('permission:users.manage');
});
