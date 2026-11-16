<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;


Route::get('/', [FrontendController::class, 'welcome'])->name('catalog.index');

// مسارات الملف الشخصي (Profile)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', fn () => auth()->user()->isAdmin()
    ? redirect()->route('admin.dashboard')
    : redirect()->route('catalog.index'))->middleware('auth')->name('dashboard');

// مسارات الأدمن المحمية (Admin Dashboard & Shell)
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // استثنينا الـ show من الـ resource عشان نعمله بشكل صريح ومضبوط بالـ admin prefix
    Route::resource('categories', CategoryController::class)
        ->except(['create', 'show', 'edit'])
        ->parameters(['categories' => 'category:slug']);

    Route::post('/customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.resetPassword');

    // مسارات العملاء (عرض وحذف)
    Route::resource('customers', CustomerController::class)->only(['index', 'destroy']);

    Route::resource('products', ProductController::class)->except(['create', 'show', 'edit']);

    // Delete One image from gallery 
    Route::delete('/product-images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    
    // تم حذف مسار الـ store الخاص بالأدمن بناءً على قرارنا بخصوص الإدخال اليدوي
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // تم تصحيح الـ Name هنا ليكون صحيحاً بدون تكرار admin.
    Route::put('/orders/{order}/reason', [OrderController::class, 'updateReason'])->name('orders.updateReason');

    Route::post('/customers/{customer}/zero-wallet', [CustomerController::class, 'zeroWallet'])->name('customers.zeroWallet');




    Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/info', [\App\Http\Controllers\Admin\ProfileController::class, 'updateInfo'])->name('profile.updateInfo');
    Route::put('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.updatePassword');



});

// مسارات السوبر أدمن فقط
Route::middleware(['auth', SuperAdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('admins', AdminUserController::class)->except(['create', 'show', 'edit']);
});

// Frontend
Route::middleware(['auth'])->group(function () {
    Route::get('/categories', [FrontendController::class, 'allCategories'])->name('shop.categories.index');

    // عرض تفاصيل القسم والمنتجات اللي جواها
    Route::get('/category/{slug}', [FrontendController::class, 'showCategory'])->name('shop.category');
    
    // صفحة تفاصيل المنتج الفردي
    Route::get('/product/{id}', [FrontendController::class, 'showProduct'])->name('shop.product.show');
});

// Merchant Routes
Route::middleware(['auth'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/orders', [App\Http\Controllers\Merchant\OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [App\Http\Controllers\Merchant\OrderController::class, 'store'])->middleware('throttle:20,1')->name('orders.store');
    Route::get('/products-search', [App\Http\Controllers\Merchant\OrderController::class, 'searchProducts'])->middleware('throttle:60,1')->name('products.search');
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\Merchant\OrderController::class, 'cancel'])->middleware('throttle:20,1')->name('orders.cancel');

    Route::get('/wallet', [App\Http\Controllers\Merchant\WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/withdraw', [App\Http\Controllers\Merchant\WalletController::class, 'withdraw'])->middleware('throttle:5,1')->name('wallet.withdraw');
});




Route::prefix('merchant')->name('merchant.')->middleware(['auth'])->group(function () {
    // صفحة البروفايل للتاجر
    Route::get('/profile', [\App\Http\Controllers\Merchant\MerchantProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/info', [\App\Http\Controllers\Merchant\MerchantProfileController::class, 'updateInfo'])->name('profile.updateInfo');
    Route::put('/profile/password', [\App\Http\Controllers\Merchant\MerchantProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::delete('/profile', [\App\Http\Controllers\Merchant\MerchantProfileController::class, 'destroy'])->name('profile.destroy');
});



require __DIR__.'/auth.php';