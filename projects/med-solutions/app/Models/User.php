<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    /** The single panel route each role lands in after sign-in. */
    public function panelRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Staff => 'staff.dashboard',
            default => 'patient.dashboard',
        };
    }

    /** Whether this user may open a given URL path (used to validate a stored "intended" URL). */
    public function canAccessPath(string $path): bool
    {
        if (str_starts_with($path, '/admin')) {
            return $this->role === UserRole::Admin;
        }
        if (str_starts_with($path, '/staff')) {
            return $this->role === UserRole::Staff;
        }
        if (str_starts_with($path, '/patient')) {
            return $this->role === UserRole::Patient;
        }

        return true;
    }

    public function canManagePortal(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }
}
