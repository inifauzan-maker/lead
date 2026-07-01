<?php

use App\Http\Controllers\Api\IntegrationController;
use Illuminate\Support\Facades\Route;

Route::middleware('integration.token')->prefix('integrations')->group(function () {
    Route::get('prospek', [IntegrationController::class, 'prospek']);
    Route::get('follow-ups', [IntegrationController::class, 'followUps']);
});
