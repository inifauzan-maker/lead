<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramLead extends Model
{
    protected $table = 'program_leads';

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
