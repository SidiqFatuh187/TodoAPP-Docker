<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DueDateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
        return view('login');
    });

    // Admin Route
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/',              [AdminController::class, 'index'])->name('index');
    Route::get('/users',         [AdminController::class, 'users'])->name('users');
    Route::get('/tasks',         [AdminController::class, 'tasks'])->name('tasks');
    Route::get('/category',    [AdminController::class, 'category'])->name('category');
    Route::get('/activity',      [AdminController::class, 'activity'])->name('activity');
    Route::get('/export',        [AdminController::class, 'export'])->name('export');
    Route::delete('/activity/clear-all}', [AdminController::class, 'destroyAllActivitylogs'])->name('activity.clearAll');
    Route::delete('/activity/{activity}', [AdminController::class, 'destroyActivitylog'])->name('activity.destroy');
 
    Route::patch('/users/{user}/ban',   [AdminController::class, 'ban'])->name('users.ban');
    Route::patch('/users/{user}/unban', [AdminController::class, 'unban'])->name('users.unban');
    });

    // Authentication routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'index'])->name('login');
        Route::post('/login', [LoginController::class, 'store']);
        Route::get('/register', [RegisterController::class, 'index'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    });

Route::middleware('auth')->group(function () {
        // dashboard route
     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // category routes
    Route::prefix('category')->name('category.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/store', [CategoryController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy'])->name('delete');
    });

    Route::prefix('todo')->name('todo.')->group(function () {
        Route::get('/', [TodoController::class, 'index'])->name('index');
        Route::get('/search', [TodoController::class, 'search'])->name('search');  
        Route::get('/create', [TodoController::class, 'create'])->name('create');
        Route::post('/store', [TodoController::class, 'store'])->name('store');
        Route::get('/edit/{id}', [TodoController::class, 'edit'])->name('edit');
        Route::put('/update/{id}', [TodoController::class, 'update'])->name('update');
        Route::patch('/status/{id}', [TodoController::class, 'updateStatus'])->name('status');
        Route::delete('/delete/{id}', [TodoController::class, 'destroy'])->name('delete');
        Route::get('/{id}', [TodoController::class, 'show'])->name('show');        
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notification/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notification.markAllRead');
    Route::delete('/notifications/destroy-all', [NotificationController::class, 'destroyAllnotifications'])->name('notification.destroyAll');
    Route::delete('/notification/{id}',        [NotificationController::class, 'destroy'])->name('notification.destroy');

    Route::get('/due-dates', [DueDateController::class, 'index'])->name('due-dates.index');

    Route::prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('update');
    Route::get('/password', [ProfileController::class, 'passwordForm'])->name('password');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    });

 });
    
    // Password Reset Routes 
    Route::prefix('password')->name('password.')->group(function () {
        Route::get('/forgot', [PasswordResetController::class, 'forgot'])->name('forgot');
        Route::post('/forgot', [PasswordResetController::class, 'sendOtp'])->name('forgot.send');
        
        Route::get('/verify', [PasswordResetController::class, 'verify'])->name('verify');
        Route::post('/verify', [PasswordResetController::class, 'verifyOtp'])->name('verify.submit');
        Route::get('/reset', [PasswordResetController::class, 'reset'])->name('reset');
        Route::post('/reset', [PasswordResetController::class, 'updatePassword'])->name('reset.submit');
    });

// Logout route
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');