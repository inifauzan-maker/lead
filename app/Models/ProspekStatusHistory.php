<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProspekStatusHistory extends Model
{
    protected $fillable = [
        'prospek_id',
        'user_id',
        'status_lama',
        'status_baru',
        'sumber',
        'catatan',
    ];

    public function prospek(): BelongsTo
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
