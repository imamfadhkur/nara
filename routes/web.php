<?php

use App\Http\Controllers\Panel\GoalController;
use App\Http\Controllers\Panel\LifeDomainController;
use App\Http\Controllers\Panel\ProjectController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\TaskController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('panel.projects.index')
        : redirect()->route('login');
});

Route::redirect('/dashboard', '/panel/projects')
    ->middleware(['auth'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Panel Native NARA (Bagian 3.7)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'two-factor'])
    ->prefix('panel')
    ->name('panel.')
    ->group(function () {
        Route::redirect('/', '/panel/projects')->name('home');

        Route::resource('life-domains', LifeDomainController::class);
        Route::resource('goals', GoalController::class);
        Route::resource('projects', ProjectController::class);
        Route::resource('tasks', TaskController::class);

        Route::post('tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])
            ->name('tasks.subtasks.store');
        Route::patch('tasks/{task}/subtasks/{subtask}/toggle', [TaskController::class, 'toggleSubtask'])
            ->name('tasks.subtasks.toggle');
        Route::delete('tasks/{task}/subtasks/{subtask}', [TaskController::class, 'destroySubtask'])
            ->name('tasks.subtasks.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/two-factor', [SettingsController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
        Route::post('settings/two-factor/confirm', [SettingsController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
        Route::post('settings/two-factor/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.two-factor.recovery');
        Route::delete('settings/two-factor', [SettingsController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
