<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\TestController as AdminTestController;
use App\Http\Controllers\Admin\TheoryController as AdminTheoryController;
use App\Http\Controllers\Admin\TrainerController as AdminTrainerController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\YandexController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiaryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReflectionController;
use App\Http\Controllers\ShortController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TheoryController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/credits', 'legal.credits')->name('credits');

Route::post('/preferences', [PreferenceController::class, 'store'])->name('preferences.store');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::get('/auth/yandex', [YandexController::class, 'redirect'])->name('auth.yandex');
Route::get('/auth/yandex/callback', [YandexController::class, 'callback'])->name('auth.yandex.callback');
Route::post('/logout', [YandexController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dev-login', [DevLoginController::class, 'show'])->name('dev.login');
Route::post('/dev-login', [DevLoginController::class, 'store'])->name('dev.login.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'onboarded'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/progress/feedback', [ProgressController::class, 'feedback'])->name('progress.feedback');
    Route::get('/certificate', [ProgressController::class, 'certificate'])->name('certificate.show');

    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/{module:slug}', [ModuleController::class, 'show'])->name('modules.show');

    Route::get('/shorts', [ShortController::class, 'index'])->name('shorts.index');
    Route::post('/shorts/{short:slug}/like', [ShortController::class, 'like'])->name('shorts.like');
    Route::post('/shorts/{short:slug}/view', [ShortController::class, 'view'])->name('shorts.view');

    Route::get('/trainers/{trainer:slug}', [TrainerController::class, 'show'])->name('trainers.show');
    Route::post('/trainers/{trainer:slug}', [TrainerController::class, 'submit'])->middleware('throttle:30,1')->name('trainers.submit');
    Route::get('/attempts/{attempt}', [TrainerController::class, 'result'])->name('trainers.result');
    Route::post('/attempts/{attempt}/reflection', [ReflectionController::class, 'store'])->middleware('throttle:20,1')->name('reflections.store');

    Route::get('/diagnostics', [TestController::class, 'index'])->name('tests.index');
    Route::get('/diagnostics/{test:slug}', [TestController::class, 'show'])->name('tests.show');
    Route::get('/diagnostics/{test:slug}/history', [TestController::class, 'history'])->name('tests.history');
    Route::post('/diagnostics/{test:slug}/start', [TestController::class, 'start'])->middleware('throttle:20,1')->name('tests.start');
    Route::get('/test-attempts/{attempt}', [TestController::class, 'take'])->name('tests.take');
    Route::post('/test-attempts/{attempt}/autosave', [TestController::class, 'autosave'])->middleware('throttle:60,1')->name('tests.autosave');
    Route::post('/test-attempts/{attempt}', [TestController::class, 'submit'])->middleware('throttle:10,1')->name('tests.submit');
    Route::get('/test-results/{attempt}', [TestController::class, 'result'])->name('tests.result');

    Route::get('/diary', [DiaryController::class, 'index'])->name('diary.index');
    Route::post('/diary', [DiaryController::class, 'store'])->name('diary.store');
    Route::delete('/diary/{entry}', [DiaryController::class, 'destroy'])->name('diary.destroy');

    Route::get('/theory', [TheoryController::class, 'index'])->name('theory.index');
    Route::get('/theory/{article:slug}', [TheoryController::class, 'show'])->name('theory.show');
    Route::get('/theory/{article:slug}/source', [TheoryController::class, 'openSource'])->name('theory.source');
    Route::post('/theory/{article:slug}/read', [TheoryController::class, 'markRead'])->name('theory.read');
    Route::post('/theory/{article:slug}/video', [TheoryController::class, 'markVideo'])->name('theory.video');
    Route::post('/theory/{article:slug}/task', [TheoryController::class, 'completeTask'])->name('theory.task');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/camera', [CameraController::class, 'show'])->name('camera.show');
    Route::post('/camera/consent', [CameraController::class, 'consent'])->name('camera.consent');
    Route::delete('/camera/consent', [CameraController::class, 'revokeConsent'])->name('camera.consent.revoke');
    Route::post('/camera/progress', [CameraController::class, 'progress'])->name('camera.progress');
    Route::post('/camera/submit', [CameraController::class, 'submit'])->name('camera.submit');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset', [AdminUserController::class, 'reset'])->name('users.reset');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    Route::get('/tests', [AdminTestController::class, 'index'])->name('tests.index');
    Route::get('/tests/{test}', [AdminTestController::class, 'show'])->name('tests.show');
    Route::put('/tests/{test}', [AdminTestController::class, 'update'])->name('tests.update');
    Route::post('/tests/{test}/toggle', [AdminTestController::class, 'toggle'])->name('tests.toggle');
    Route::get('/tests/{test}/items/create', [AdminTestController::class, 'createItem'])->name('tests.items.create');
    Route::post('/tests/{test}/items', [AdminTestController::class, 'storeItem'])->name('tests.items.store');
    Route::get('/tests/{test}/items/{item}/edit', [AdminTestController::class, 'editItem'])->name('tests.items.edit');
    Route::put('/tests/{test}/items/{item}', [AdminTestController::class, 'updateItem'])->name('tests.items.update');
    Route::post('/tests/{test}/items/{item}/toggle', [AdminTestController::class, 'toggleItem'])->name('tests.items.toggle');

    Route::get('/trainers', [AdminTrainerController::class, 'index'])->name('trainers.index');
    Route::get('/trainers/create', [AdminTrainerController::class, 'create'])->name('trainers.create');
    Route::post('/trainers', [AdminTrainerController::class, 'store'])->name('trainers.store');
    Route::get('/trainers/{trainer}/edit', [AdminTrainerController::class, 'edit'])->name('trainers.edit');
    Route::put('/trainers/{trainer}', [AdminTrainerController::class, 'update'])->name('trainers.update');
    Route::post('/trainers/{trainer}/toggle', [AdminTrainerController::class, 'toggle'])->name('trainers.toggle');

    Route::get('/theory', [AdminTheoryController::class, 'index'])->name('theory.index');
    Route::get('/theory/create', [AdminTheoryController::class, 'create'])->name('theory.create');
    Route::post('/theory', [AdminTheoryController::class, 'store'])->name('theory.store');
    Route::get('/theory/{article}/edit', [AdminTheoryController::class, 'edit'])->name('theory.edit');
    Route::put('/theory/{article}', [AdminTheoryController::class, 'update'])->name('theory.update');
    Route::post('/theory/{article}/toggle', [AdminTheoryController::class, 'toggle'])->name('theory.toggle');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    Route::get('/export/users', [ExportController::class, 'users'])->name('export.users');
    Route::get('/export/trainer-attempts', [ExportController::class, 'trainerAttempts'])->name('export.trainer-attempts');
    Route::get('/export/test-attempts', [ExportController::class, 'testAttempts'])->name('export.test-attempts');
});
