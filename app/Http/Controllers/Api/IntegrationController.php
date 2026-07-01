<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Prospek;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function prospek(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 100), 1), 500);

        $items = Prospek::query()
            ->orderBy('id')
            ->paginate($perPage)
            ->through(fn (Prospek $prospek) => [
                'id' => $prospek->id,
                'nama' => $prospek->nama,
                'asal_sekolah' => $prospek->asal_sekolah,
                'jenjang' => $prospek->jenjang,
                'kelas' => $prospek->kelas,
                'kota_asal' => $prospek->kota_asal,
                'no_wa' => $prospek->no_wa,
                'program' => $prospek->program,
                'status' => $prospek->status,
                'cabang' => $prospek->cabang,
                'user_id' => $prospek->user_id,
                'created_by' => $prospek->created_by,
                'diserahkan_ke' => $prospek->diserahkan_ke,
                'sumber' => $prospek->sumber,
                'keterangan' => $prospek->keterangan,
                'tgl_masuk' => $prospek->tgl_masuk?->toDateString(),
                'tanggal_daftar' => $prospek->tanggal_daftar?->toDateString(),
                'program_final' => $prospek->program_final,
                'status_pembayaran' => $prospek->status_pembayaran,
                'kelas_angkatan' => $prospek->kelas_angkatan,
                'catatan_administrasi' => $prospek->catatan_administrasi,
                'created_at' => $prospek->created_at?->toJSON(),
                'updated_at' => $prospek->updated_at?->toJSON(),
            ]);

        return response()->json($items);
    }

    public function followUps(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 100), 1), 500);

        $items = FollowUp::query()
            ->with(['prospek:id,no_wa', 'user:id,name,email'])
            ->orderBy('id')
            ->paginate($perPage)
            ->through(fn (FollowUp $followUp) => [
                'id' => $followUp->id,
                'prospek_id' => $followUp->prospek_id,
                'prospek_no_wa' => $followUp->prospek?->no_wa,
                'user_id' => $followUp->user_id,
                'user_name' => $followUp->user?->name,
                'user_email' => $followUp->user?->email,
                'tanggal_follow_up' => $followUp->tanggal_follow_up?->toJSON(),
                'metode' => $followUp->metode,
                'hasil' => $followUp->hasil,
                'catatan' => $followUp->catatan,
                'tindak_lanjut' => $followUp->tindak_lanjut,
                'tanggal_follow_up_berikutnya' => $followUp->tanggal_follow_up_berikutnya?->toDateString(),
                'prioritas' => $followUp->prioritas,
                'created_at' => $followUp->created_at?->toJSON(),
                'updated_at' => $followUp->updated_at?->toJSON(),
            ]);

        return response()->json($items);
    }
}
