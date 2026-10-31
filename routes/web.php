<?php

use App\Http\Controllers\Panel\CalendarController;
use App\Http\Controllers\Panel\DocumentationController;
use App\Http\Controllers\Panel\FocusController;
use App\Http\Controllers\Panel\GoalController;
use App\Http\Controllers\Panel\InboxController;
use App\Http\Controllers\Panel\KanbanController;
use App\Http\Controllers\Panel\LifeDomainController;
use App\Http\Controllers\Panel\MatrixController;
use App\Http\Controllers\Panel\OutputController;
use App\Http\Controllers\Panel\ProjectController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\TaskController;
use App\Http\Controllers\Panel\TimelineController;
use App\Http\Controllers\Panel\TodayController;
use App\Http\Controllers\Panel\WeeklyReviewController;
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

        // Fase 2: Kalender, Timeline, Matrix, Weekly Review, & Focus Mode
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::post('calendar/schedules', [CalendarController::class, 'store'])->name('calendar.store');
        Route::delete('calendar/schedules/{schedule}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
        Route::post('calendar/sync', [CalendarController::class, 'sync'])->name('calendar.sync');

        Route::get('timeline', [TimelineController::class, 'index'])->name('timeline.index');
        Route::get('matrix', [MatrixController::class, 'index'])->name('matrix.index');
        Route::resource('weekly-reviews', WeeklyReviewController::class);

        Route::get('focus', [FocusController::class, 'index'])->name('focus');
        Route::post('focus', [FocusController::class, 'store'])->name('focus.store');

        Route::resource('life-domains', LifeDomainController::class);
        Route::resource('goals', GoalController::class);
        Route::resource('projects', ProjectController::class);
        Route::resource('tasks', TaskController::class);

        // Outputs (D-16, Bagian 5.4 & 9.8)
        Route::resource('outputs', OutputController::class)->only(['index', 'store', 'destroy']);
        Route::get('outputs/{output}/download/{file}', [OutputController::class, 'downloadFile'])->name('outputs.download');
        Route::get('outputs/{output}/view/{file}', [OutputController::class, 'viewFile'])->name('outputs.view');

        Route::post('tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])
            ->name('tasks.subtasks.store');
        Route::patch('tasks/{task}/subtasks/{subtask}/toggle', [TaskController::class, 'toggleSubtask'])
            ->name('tasks.subtasks.toggle');
        Route::delete('tasks/{task}/subtasks/{subtask}', [TaskController::class, 'destroySubtask'])
            ->name('tasks.subtasks.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/integrations/telegram', [SettingsController::class, 'updateTelegramIntegration'])->name('settings.integrations.telegram');
        Route::post('settings/integrations/google', [SettingsController::class, 'updateGoogleIntegration'])->name('settings.integrations.google');
        Route::get('settings/integrations/google/redirect', [SettingsController::class, 'redirectToGoogle'])->name('settings.integrations.google.redirect');
        Route::get('settings/integrations/google/callback', [SettingsController::class, 'handleGoogleCallback'])->name('settings.integrations.google.callback');
        Route::post('settings/integrations/google/disconnect', [SettingsController::class, 'disconnectGoogle'])->name('settings.integrations.google.disconnect');
        Route::post('settings/integrations/{provider}/toggle', [SettingsController::class, 'toggleIntegration'])->name('settings.integrations.toggle');
        Route::post('settings/test-notification', [SettingsController::class, 'testNotification'])->name('settings.test-notification');
        Route::post('settings/two-factor', [SettingsController::class, 'enableTwoFactor'])->name('settings.two-factor.enable');
        Route::post('settings/two-factor/confirm', [SettingsController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
        Route::post('settings/two-factor/recovery-codes', [SettingsController::class, 'regenerateRecoveryCodes'])->name('settings.two-factor.recovery');
        Route::delete('settings/two-factor', [SettingsController::class, 'disableTwoFactor'])->name('settings.two-factor.disable');

        // Dokumentasi & Basis Pengetahuan Sistem (Fase 2)
        Route::get('docs', [DocumentationController::class, 'index'])->name('docs.index');
        Route::get('docs/create', [DocumentationController::class, 'create'])->name('docs.create');
        Route::post('docs', [DocumentationController::class, 'store'])->name('docs.store');

        // Manajemen Kategori Dokumentasi
        Route::post('docs/categories', [DocumentationController::class, 'storeCategory'])->name('docs.categories.store');
        Route::put('docs/categories/{category}', [DocumentationController::class, 'updateCategory'])->name('docs.categories.update');
        Route::delete('docs/categories/{category}', [DocumentationController::class, 'destroyCategory'])->name('docs.categories.destroy');

        Route::get('docs/{article:slug}', [DocumentationController::class, 'show'])->name('docs.show');
        Route::get('docs/{article}/edit', [DocumentationController::class, 'edit'])->name('docs.edit');
        Route::put('docs/{article}', [DocumentationController::class, 'update'])->name('docs.update');
        Route::delete('docs/{article}', [DocumentationController::class, 'destroy'])->name('docs.destroy');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
