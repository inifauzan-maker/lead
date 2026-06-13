<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cabang',
        'aktif',
        'facebook',
        'instagram',
        'tiktok',
        'blog',
        'youtube',
    ];

    public function roleLabel(): string
    {
        return match ($this->role) {
            'superadmin' => 'Superadmin',
            'admin' => 'Admin',
            'leader' => 'Leader',
            'direksi' => 'Direksi',
            default => 'Staff',
        };
    }

    public function bisaKelolaPengguna(): bool
    {
        return $this->role === 'superadmin';
    }

    public function bisaLihatSemuaLeads(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'leader', 'staff', 'direksi'], true);
    }

    public function bisaInputLeads(): bool
    {
        return ! $this->hanyaLihatLeads();
    }

    public function bisaMengubahSemuaLeads(): bool
    {
        return $this->role === 'superadmin';
    }

    public function bisaMengubahLeadsCabang(): bool
    {
        return in_array($this->role, ['admin', 'leader'], true);
    }

    public function bisaMengubahLeadsMilikSendiri(): bool
    {
        return $this->role === 'staff';
    }

    public function hanyaLihatLeads(): bool
    {
        return $this->role === 'direksi';
    }

    public function aksesSemuaCabang(): bool
    {
        return in_array($this->role, ['superadmin', 'direksi'], true);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function courseProgress(): HasMany
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(SistemNotification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

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
            'aktif' => 'boolean',
        ];
    }
}
