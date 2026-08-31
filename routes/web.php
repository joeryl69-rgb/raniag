<?php

use App\Http\Controllers\Agency\FeedbackController as StaffSupportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/public.php';
require __DIR__.'/admin.php';
require __DIR__.'/agency.php';
require __DIR__.'/personnel.php';

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->homeRoute());
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    // Support Center for signed-in staff (agency + personnel accounts).
    // Shared here (instead of duplicated in agency.php/personnel.php)
    // since both roles get identical access to file a message.
    // NOTE: path is deliberately NOT "/support" — that URI already belongs
    // to the public route in routes/public.php (public.support). Two
    // routes can't share the same method+URI: the one registered first
    // (public.php, required above) would always win the match, so signed-in
    // staff visiting "/support" would silently land on the public form.
    Route::prefix('support-center')->name('agency.support.')->group(function () {
        Route::get('/', [StaffSupportController::class, 'create'])->name('create');
        Route::post('/', [StaffSupportController::class, 'store'])->name('store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/poll', [NotificationController::class, 'poll'])->name('poll');
        Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('mark_read');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('mark_all_read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/bulk/delete', [NotificationController::class, 'destroySelected'])->name('destroy_selected');
        Route::delete('/bulk/delete-all', [NotificationController::class, 'destroyAll'])->name('destroy_all');
    });
});

Route::get('/debug-session', function () {
    return response()->json([
        'session_id'          => session()->getId(),
        'csrf_token'          => csrf_token(),
        'is_secure'           => request()->isSecure(),
        'x_forwarded_proto'   => request()->header('X-Forwarded-Proto'),
        'scheme'              => request()->getScheme(),
        'session_cookie_seen' => request()->hasCookie(config('session.cookie')),
        'session_cookie_name' => config('session.cookie'),
        'session_driver'      => config('session.driver'),
        'session_secure_cfg'  => config('session.secure'),
        'app_url'             => config('app.url'),
    ]);
});

require __DIR__.'/auth.php';