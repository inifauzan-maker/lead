<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospek_id',
        'user_id',
        'tanggal_follow_up',
        'metode',
        'hasil',
        'catatan',
        'tindak_lanjut',
        'tanggal_follow_up_berikutnya',
        'prioritas',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_follow_up' => 'datetime',
            'tanggal_follow_up_berikutnya' => 'date',
        ];
    }

    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sudahSelesai(): bool
    {
        return in_array($this->hasil, ['Closing', 'Tidak tertarik'], true);
    }

    public function overdue(): bool
    {
        return $this->tanggal_follow_up_berikutnya
            && $this->tanggal_follow_up_berikutnya->isPast()
            && ! $this->tanggal_follow_up_berikutnya->isToday()
            && ! $this->sudahSelesai();
    }

    public function jatuhTempoHariIni(): bool
    {
        return $this->tanggal_follow_up_berikutnya
            && $this->tanggal_follow_up_berikutnya->isToday()
            && ! $this->sudahSelesai();
    }

    public function statusJadwal(): string
    {
        if (! $this->tanggal_follow_up_berikutnya) {
            return 'Belum dijadwalkan';
        }

        if ($this->overdue()) {
            return 'Overdue';
        }

        if ($this->jatuhTempoHariIni()) {
            return 'Hari ini';
        }

        return 'Terjadwal';
    }
}
