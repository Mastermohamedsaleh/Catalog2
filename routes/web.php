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
    // /profile just sends each user to the page that matches their role
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:5,1')->name('profile.destroy');
    Route::get('/merchant/profile', [ProfileController::class, 'merchant'])->name('merchant.profile');
});

Route::get('/dashboard', fn () => auth()->user()->isAdmin()
    ? redirect()->route('admin.dashboard')
    : redirect()->route('catalog.index'))->middleware('auth')->name('dashboard');

// مسارات الأدمن المحمية (Admin Dashboard & Shell)
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'admin'])->name('profile');
    // اسم الراوت اللي صفحة بروفايل الأدمن القديمة بتستدعيه: admin.profile.updatePassword
    // بيستخدم نفس كنترولر تغيير الباسورد الأساسي (فيه التحقق من الباسورد الحالي وتأكيد الباسورد الجديد).
    // بيقبل PUT و POST عشان الفورم يشتغل سواء فيه @method('PUT') أو من غيره.
    Route::match(['put', 'post'], '/profile/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])
        ->middleware('throttle:6,1')->name('profile.updatePassword');
    
    // استثنينا الـ show من الـ resource عشان نعمله بشكل صريح ومضبوط بالـ admin prefix
    Route::resource('categories', CategoryController::class)
        ->except(['create', 'show', 'edit'])
        ->parameters(['categories' => 'category:slug']);

    Route::post('/customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.resetPassword');

    // مسارات العملاء: العرض للأدمن العادي. الحذف وتصفير المحفظة للسوبر أدمن بس (أسفل في مجموعة super).
    Route::resource('customers', CustomerController::class)->only(['index']);

    Route::resource('products', ProductController::class)->except(['create', 'show', 'edit']);

    // Delete One image from gallery 
    Route::delete('/product-images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    
    // تم حذف مسار الـ store الخاص بالأدمن بناءً على قرارنا بخصوص الإدخال اليدوي
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // تم تصحيح الـ Name هنا ليكون صحيحاً بدون تكرار admin.
    Route::put('/orders/{order}/reason', [OrderController::class, 'updateReason'])->name('orders.updateReason');
});

// مسارات السوبر أدمن فقط
Route::middleware(['auth', SuperAdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('admins', AdminUserController::class)->except(['create', 'show', 'edit']);

    // الحاجات الحساسة (فلوس وحذف حسابات): السوبر أدمن بس. الأدمن العادي يشوف العملاء ويعيد تعيين كلمة السر فقط.
    Route::post('/customers/{customer}/zero-wallet', [CustomerController::class, 'zeroWallet'])->name('customers.zeroWallet');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
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

require __DIR__.'/auth.php';