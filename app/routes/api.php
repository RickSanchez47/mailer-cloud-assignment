<?php

use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Public\PublicFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authenticated, tenant-scoped dashboard/API routes
|--------------------------------------------------------------------------
| Requires an authenticated user with a $user->account relation (e.g. via
| Sanctum). Auth scaffolding itself is intentionally out of scope for this
| slice — see README_INTEGRATION.md.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/forms', [FormController::class, 'index']);
    Route::post('/forms', [FormController::class, 'store']);
    Route::put('/forms/{form}/draft', [FormController::class, 'updateDraft']);
    Route::post('/forms/{form}/publish', [FormController::class, 'publish']);

    Route::get('/forms/{form}/submissions', [SubmissionController::class, 'index']);
    Route::get('/forms/{form}/submissions/export', [SubmissionController::class, 'export']);
});

/*
|--------------------------------------------------------------------------
| Public, anonymous, cross-origin routes
|--------------------------------------------------------------------------
| These live under routes/api.php (not web.php) specifically to stay
| stateless and CSRF-exempt: an embedded form on a customer's site submits
| here directly from the browser, cross-origin, with no session.
*/
Route::prefix('public/{accountApiKey}/forms/{slug}')->group(function () {
    Route::get('/', [PublicFormController::class, 'show']);
    Route::post('/submit', [PublicFormController::class, 'submit']);
});
