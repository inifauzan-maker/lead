<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SistemNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'tipe',
        'judul',
        'pesan',
        'tautan',
        'prioritas',
        'dibaca_pada',
    ];

    protected function casts(): array
    {
        return [
            'dibaca_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function kirim(iterable $penerima, array $data): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        collect($penerima)
            ->filter()
            ->unique('id')
            ->each(function (User $user) use ($data) {
                self::create([
                    'user_id' => $user->id,
                    'tipe' => $data['tipe'] ?? 'info',
                    'judul' => $data['judul'],
                    'pesan' => $data['pesan'] ?? null,
                    'tautan' => $data['tautan'] ?? null,
                    'prioritas' => $data['prioritas'] ?? 'Normal',
                    'dibaca_pada' => null,
                ]);
            });
    }

    public static function kirimSekali(iterable $penerima, array $data): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        collect($penerima)
            ->filter()
            ->unique('id')
            ->each(function (User $user) use ($data) {
                $sudahAda = self::query()
                    ->where('user_id', $user->id)
                    ->where('tipe', $data['tipe'] ?? 'info')
                    ->where('judul', $data['judul'])
                    ->where('tautan', $data['tautan'] ?? null)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if ($sudahAda) {
                    return;
                }

                self::create([
                    'user_id' => $user->id,
                    'tipe' => $data['tipe'] ?? 'info',
                    'judul' => $data['judul'],
                    'pesan' => $data['pesan'] ?? null,
                    'tautan' => $data['tautan'] ?? null,
                    'prioritas' => $data['prioritas'] ?? 'Normal',
                    'dibaca_pada' => null,
                ]);
            });
    }

    public static function penerimaCabang(?string $cabang, array $roleCabang = ['admin', 'leader']): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return User::query()
            ->where('aktif', true)
            ->where(function ($query) use ($cabang, $roleCabang) {
                $query->whereIn('role', ['superadmin', 'direksi'])
                    ->orWhere(function ($query) use ($cabang, $roleCabang) {
                        $query->whereIn('role', $roleCabang);

                        if ($cabang) {
                            $query->where('cabang', $cabang);
                        }
                    });
            })
            ->get();
    }
}
