<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'nama',
        'isi_pesan',
        'aktif',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public static function aktifDefault(): ?self
    {
        return self::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('nama')
            ->first();
    }

    public function isiUntuk(Prospek $prospek, ?User $user): string
    {
        return strtr($this->isi_pesan, [
            '{nama}' => $prospek->nama,
            '{asal_sekolah}' => $prospek->asal_sekolah ?: '-',
            '{kelas}' => $prospek->kelas ?: '-',
            '{jenjang}' => $prospek->jenjang ?: '-',
            '{kota_asal}' => $prospek->kota_asal ?: '-',
            '{program}' => $prospek->program_final ?: ($prospek->program ?: '-'),
            '{status}' => $prospek->status,
            '{cabang}' => $prospek->cabang ?: '-',
            '{user}' => $user?->name ?: '-',
        ]);
    }
}
