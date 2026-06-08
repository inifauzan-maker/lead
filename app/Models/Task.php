<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'deskripsi',
        'status',
        'prioritas',
        'tenggat',
        'prospek_id',
        'assigned_to',
        'created_by',
        'cabang',
    ];

    protected function casts(): array
    {
        return [
            'tenggat' => 'date',
        ];
    }

    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class);
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }
}
