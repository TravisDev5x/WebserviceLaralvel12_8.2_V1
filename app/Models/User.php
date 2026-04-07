<?php

namespace App\Models;

// TODO: Restaurar "implements MustVerifyEmail" cuando el envío de correo esté configurado y se reactive la verificación obligatoria.
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\UserRegistrationEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'employee_number',
        'role',
        'is_active',
        'email',
        'email_verified_at',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $r = $user->role;
            if ($r === null || $r === '') {
                $user->role = 'viewer';
            }
        });
    }

    public function usesSyntheticEmail(): bool
    {
        return UserRegistrationEmail::isSynthetic($this->email);
    }
}
