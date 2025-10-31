<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', [ItemsController::class,'index'])->name('items.index');
Route::get('/item/{item_id}', [ItemsController::class, 'show'])->name('items.show');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/sell', [ItemsController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemsController::class, 'store'])->name('items.store');
    Route::post('/items/{item_id}/comments', [CommentController::class, 'store'])->name('items.comments.store');
    Route::post('/items/{item_id}/like', [LikeController::class, 'store'])->name('items.like');
    Route::delete('/items/{item_id}/like', [LikeController::class,'destroy'])->name('items.unlike');

    Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])->name('purchase.show');
    Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/purchase/address/{item_id}', [PurchaseController::class, 'address'])->name('purchase.address');
    Route::post('/purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])->name('purchase.address.update');
    Route::post('/purchase/{item_id}/payment', [PurchaseController::class, 'updatePayment'])->name('purchase.payment.update');

    Route::post('/payments/checkout/{order}', [StripeController::class, 'checkout'])->name('payments.checkout');
    Route::get('/payments/success', [StripeController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel', [StripeController::class, 'cancel'])->name('payments.cancel');

    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');

});
