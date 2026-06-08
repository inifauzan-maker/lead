<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prospek extends Model
{
    use HasFactory;

    protected $table = 'prospek';

    protected $fillable = [
        'nama',
        'asal_sekolah',
        'kelas',
        'kota_asal',
        'no_wa',
        'program',
        'status',
        'cabang',
        'user_id',
        'diserahkan_ke',
        'sumber',
        'keterangan',
        'tgl_masuk',
    ];

    protected function casts(): array
    {
        return [
            'tgl_masuk' => 'date',
        ];
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function followUpTerakhir()
    {
        return $this->hasOne(FollowUp::class)->latestOfMany('tanggal_follow_up');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
