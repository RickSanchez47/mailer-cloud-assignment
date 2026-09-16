<?php

use App\Http\Controllers\Web\BuilderController;
use App\Http\Controllers\Web\PublicFormViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Demo dashboard (session-scoped, stands in for real account auth)
|--------------------------------------------------------------------------
*/
Route::get('/builder', [BuilderController::class, 'chooseAccount'])->name('builder.choose-account');
Route::post('/builder/accounts', [BuilderController::class, 'createAccount'])->name('builder.create-account');
Route::post('/builder/accounts/{account}/use', [BuilderController::class, 'useAccount'])->name('builder.use-account');

Route::middleware('account.selected')->group(function () {
    Route::get('/builder/forms', [BuilderController::class, 'index'])->name('builder.forms.index');
    Route::get('/builder/forms/create', [BuilderController::class, 'create'])->name('builder.forms.create');
    Route::post('/builder/forms', [BuilderController::class, 'store'])->name('builder.forms.store');
    Route::get('/builder/forms/{form}/edit', [BuilderController::class, 'edit'])->name('builder.forms.edit');
    Route::put('/builder/forms/{form}/draft', [BuilderController::class, 'updateDraft'])->name('builder.forms.update-draft');
    Route::post('/builder/forms/{form}/publish', [BuilderController::class, 'publish'])->name('builder.forms.publish');
    Route::get('/builder/forms/{form}/submissions', [BuilderController::class, 'submissions'])->name('builder.forms.submissions');
    Route::get('/builder/forms/{form}/submissions/export', [BuilderController::class, 'export'])->name('builder.forms.export');
});

/*
|--------------------------------------------------------------------------
| Public form page (renders the schema dynamically via public/js/public-form.js)
|--------------------------------------------------------------------------
*/
Route::get('/forms/{accountApiKey}/{slug}', [PublicFormViewController::class, 'show'])->name('public-form.show');
