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
        'tanggal_daftar',
        'program_final',
        'nominal_pembayaran',
        'status_pembayaran',
        'kelas_angkatan',
        'catatan_administrasi',
    ];

    protected function casts(): array
    {
        return [
            'tgl_masuk' => 'date',
            'tanggal_daftar' => 'date',
            'nominal_pembayaran' => 'decimal:2',
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

    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(ProspekStatusHistory::class);
    }

    public function followUpTerakhir()
    {
        return $this->hasOne(FollowUp::class)->latestOfMany('tanggal_follow_up');
    }

    public function followUpBerikutnya()
    {
        return $this->hasOne(FollowUp::class)
            ->whereNotNull('tanggal_follow_up_berikutnya')
            ->whereNotIn('hasil', ['Closing', 'Tidak tertarik'])
            ->orderBy('tanggal_follow_up_berikutnya')
            ->orderBy('id');
    }

    public function statusFollowUp(): string
    {
        $jadwal = $this->followUpBerikutnya;

        if (! $jadwal) {
            return $this->follow_ups_count > 0 ? 'Belum dijadwalkan' : 'Belum follow up';
        }

        return $jadwal->statusJadwal();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function noWaUntuk(?User $user): string
    {
        if (blank($this->no_wa)) {
            return '-';
        }

        if ($user && (int) $this->user_id === (int) $user->id) {
            return $this->no_wa;
        }

        return $this->samarkanDuaDigitTerakhir($this->no_wa);
    }

    public function bisaDiubahOleh(?User $user): bool
    {
        if (! $user || $user->hanyaLihatLeads()) {
            return false;
        }

        if ($user->bisaMengubahSemuaLeads()) {
            return true;
        }

        if ($user->bisaMengubahLeadsMilikSendiri()) {
            return (int) $this->user_id === (int) $user->id;
        }

        if ($user->bisaMengubahLeadsCabang()) {
            return $this->cabang === $user->cabang;
        }

        return false;
    }

    private function samarkanDuaDigitTerakhir(string $nomor): string
    {
        $panjang = mb_strlen($nomor);

        if ($panjang <= 2) {
            return str_repeat('x', $panjang);
        }

        return mb_substr($nomor, 0, $panjang - 2).'xx';
    }
}
