<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetKinerja extends Model
{
    protected $table = 'target_kinerja';

    protected $fillable = [
        'bulan',
        'tahun',
        'tipe',
        'cabang',
        'user_id',
        'target_leads',
        'target_closing',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'target_leads' => 'integer',
            'target_closing' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
