<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'nama_user',
        'role',
        'cabang',
        'aksi',
        'modul',
        'deskripsi',
        'method',
        'route_name',
        'url',
        'ip_address',
        'user_agent',
        'status_code',
        'payload',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(Request $request, ?Response $response = null, ?string $aksi = null, ?string $deskripsi = null): void
    {
        try {
            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            $user = $request->user();
            $route = $request->route();

            self::create([
                'user_id' => $user?->id,
                'nama_user' => $user?->name,
                'role' => $user?->role,
                'cabang' => $user?->cabang,
                'aksi' => $aksi ?: self::aksiDariMethod($request->method()),
                'modul' => self::modulDariRequest($request),
                'deskripsi' => $deskripsi ?: self::deskripsiDariRequest($request),
                'method' => $request->method(),
                'route_name' => $route?->getName(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'status_code' => $response?->getStatusCode(),
                'payload' => self::payloadAman($request),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function aksiDariMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'POST' => 'Tambah data',
            'PUT', 'PATCH' => 'Perbarui data',
            'DELETE' => 'Hapus data',
            default => 'Akses halaman',
        };
    }

    private static function modulDariRequest(Request $request): ?string
    {
        $namaRoute = $request->route()?->getName();
        $segmen = $namaRoute ? explode('.', $namaRoute)[0] : $request->segment(1);

        return match ($segmen) {
            'prospek' => 'Data Leads',
            'follow-up' => 'Follow Up',
            'data-siswa' => 'Data Siswa',
            'pengaturan' => 'Pengaturan',
            'pengguna' => 'Pengguna',
            'profil' => 'Profil User',
            'notifikasi' => 'Notifikasi',
            default => $segmen ? ucfirst(str_replace('-', ' ', $segmen)) : 'Dashboard',
        };
    }

    private static function deskripsiDariRequest(Request $request): string
    {
        $route = $request->route()?->getName();
        $modul = self::modulDariRequest($request);

        return trim(self::aksiDariMethod($request->method()).' pada '.$modul.($route ? " ({$route})" : ''));
    }

    private static function payloadAman(Request $request): array
    {
        $data = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
            'remember_token',
            'token',
        ]);

        return self::potongNilai($data);
    }

    private static function potongNilai(mixed $nilai): mixed
    {
        if (is_array($nilai)) {
            return collect($nilai)
                ->map(fn ($item) => self::potongNilai($item))
                ->all();
        }

        if (is_string($nilai)) {
            return mb_strimwidth($nilai, 0, 400, '...');
        }

        return $nilai;
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
