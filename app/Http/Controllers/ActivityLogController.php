<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with('user')
            ->latest();

        $query
            ->when($request->filled('q'), function ($query) use ($request) {
                $kata = '%'.$request->q.'%';

                $query->where(function ($query) use ($kata) {
                    $query->where('nama_user', 'like', $kata)
                        ->orWhere('deskripsi', 'like', $kata)
                        ->orWhere('modul', 'like', $kata)
                        ->orWhere('route_name', 'like', $kata)
                        ->orWhere('url', 'like', $kata);
                });
            })
            ->when($request->filled('aksi'), fn ($query) => $query->where('aksi', $request->aksi))
            ->when($request->filled('modul'), fn ($query) => $query->where('modul', $request->modul))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->user_id))
            ->when($request->filled('tanggal_mulai'), fn ($query) => $query->whereDate('created_at', '>=', $request->tanggal_mulai))
            ->when($request->filled('tanggal_selesai'), fn ($query) => $query->whereDate('created_at', '<=', $request->tanggal_selesai));

        return view('log-aktivitas.index', [
            'items' => $query->paginate(20)->withQueryString(),
            'aksi' => ActivityLog::query()->select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
            'modul' => ActivityLog::query()->select('modul')->whereNotNull('modul')->distinct()->orderBy('modul')->pluck('modul'),
            'pengguna' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }
}
