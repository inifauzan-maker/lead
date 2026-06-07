<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModulController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ProspekController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::post('login', [AuthController::class, 'masuk'])->name('login.masuk');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'keluar'])->name('logout');
    Route::prefix('profil')->name('profil.')->group(function () {
        Route::get('/', [ProfilController::class, 'index'])->name('index');
        Route::put('/', [ProfilController::class, 'update'])->name('update');
        Route::get('tim', [ModulController::class, 'tim'])->name('tim');
        Route::get('tugas', [ModulController::class, 'tugas'])->name('tugas');
        Route::get('laporan', [ModulController::class, 'laporan'])->name('laporan');
        Route::get('pembelajaran', [ModulController::class, 'pembelajaran'])->name('pembelajaran');
    });
    Route::get('/', [ProspekController::class, 'dashboard'])->name('dashboard');
    Route::get('prospek/export', [ProspekController::class, 'export'])->name('prospek.export');
    Route::get('prospek/contoh-import', [ProspekController::class, 'contohImport'])->name('prospek.contoh-import');
    Route::post('prospek/import', [ProspekController::class, 'import'])->name('prospek.import');
    Route::post('prospek/aksi-massal', [ProspekController::class, 'aksiMassal'])->name('prospek.aksi-massal');
    Route::get('follow-up', [ProspekController::class, 'followUp'])->name('follow-up.index');
    Route::get('data-siswa', [ProspekController::class, 'dataSiswa'])->name('data-siswa.index');
    Route::resource('prospek', ProspekController::class)->except(['show']);
    Route::prefix('pengaturan')->name('pengaturan.')->middleware('role:superadmin')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('index');
        Route::post('cabang', [PengaturanController::class, 'storeCabang'])->name('cabang.store');
        Route::put('cabang/{cabang}', [PengaturanController::class, 'updateCabang'])->name('cabang.update');
        Route::delete('cabang/{cabang}', [PengaturanController::class, 'destroyCabang'])->name('cabang.destroy');
        Route::post('sumber', [PengaturanController::class, 'storeSumber'])->name('sumber.store');
        Route::put('sumber/{sumber}', [PengaturanController::class, 'updateSumber'])->name('sumber.update');
        Route::delete('sumber/{sumber}', [PengaturanController::class, 'destroySumber'])->name('sumber.destroy');
        Route::post('program', [PengaturanController::class, 'storeProgram'])->name('program.store');
        Route::put('program/{program}', [PengaturanController::class, 'updateProgram'])->name('program.update');
        Route::delete('program/{program}', [PengaturanController::class, 'destroyProgram'])->name('program.destroy');
        Route::put('user/{user}/role', [PengaturanController::class, 'updateRoleUser'])->name('user-role.update');
    });
    Route::resource('pengguna', PenggunaController::class)->only(['index', 'store', 'update'])->middleware('role:superadmin');
});
