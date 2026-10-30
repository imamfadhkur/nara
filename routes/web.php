<?php

use App\Http\Controllers\Panel\GoalController;
use App\Http\Controllers\Panel\InboxController;
use App\Http\Controllers\Panel\KanbanController;
use App\Http\Controllers\Panel\LifeDomainController;
use App\Http\Controllers\Panel\OutputController;
use App\Http\Controllers\Panel\ProjectController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\TaskController;
use App\Http\Controllers\Panel\TodayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('panel.today')
        : redirect()->route('login');
});

Route::redirect('/dashboard', '/panel/today')
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
        Route::redirect('/', '/panel/today')->name('home');

        Route::get('today', TodayController::class)->name('today');

        // Inbox (Bagian 3.7 & 8.4)
        Route::get('inbox', [InboxController::class, 'index'])->name('inbox.index');
        Route::post('inbox', [InboxController::class, 'store'])->name('inbox.store');
        Route::post('inbox/{inboxItem}/triage', [InboxController::class, 'triage'])->name('inbox.triage');

        // Kanban Board (Bagian 5.2)
        Route::get('kanban', [KanbanController::class, 'index'])->name('kanban.index');
        Route::post('kanban/tasks/{task}/move', [KanbanController::class, 'move'])->name('kanban.tasks.move');

        Route::resource('life-domains', LifeDomainController::class);
        Route::resource('goals', GoalController::class);
        Route::resource('projects', ProjectController::class);
        Route::resource('tasks', TaskController::class);

        // Outputs (D-16, Bagian 5.4 & 9.8)
        Route::resource('outputs', OutputController::class)->only(['index', 'store', 'destroy']);

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
