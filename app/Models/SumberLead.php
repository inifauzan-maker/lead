<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SumberLead extends Model
{
    protected $table = 'sumber_leads';

    protected $fillable = [
        'nama',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
