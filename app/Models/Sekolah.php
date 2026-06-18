<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sekolah extends Model
{
    protected $table = 'sekolah';

    protected $fillable = [
        'nama_sekolah',
        'nama_normalized',
        'sumber',
        'created_by',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
