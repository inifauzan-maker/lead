<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
