<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UploadController;
use App\Models\Product;
use App\Http\Controllers\CategoryHomepageController;
use App\Http\Controllers\BannerHomepageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;

Route::group(['middleware' => ['cors', 'json.response']], function () {
    Route::post('/login', [ApiAuthController::class, 'login'])->name('login.api');
    Route::post('/register',[ApiAuthController::class, 'register'])->name('register.api');
    Route::get('/banner-homepages', [BannerHomepageController::class, 'index'])->name('banners.api');
    Route::get('/category-product-homepages', [CategoryHomepageController::class, 'getCategoriesWithProduct']);
    Route::post('/product/search', [ProductController::class, 'searchByName']);
    Route::get('/category/products/{id}', [CategoryController::class, 'getCategoryWithProduct']);
});
Route::middleware('auth:api')->group(function () {
    Route::get('/overview', [OrderController::class, 'overview'])->middleware('api.admin');

    Route::get('/users', [UserController::class, 'getAll'])->middleware('api.admin');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('api.admin');
    Route::delete('/users/{id}', [UserController::class, 'delete'])->middleware('api.admin');
    // our routes to be protected will go in here
    Route::get('/categories', [CategoryController::class, 'getData'])->middleware('api.admin')->name('categories');
    Route::get('/categories/{id}', [CategoryController::class, 'getCategory'])->middleware('api.admin')->name('category');
    Route::post('/categories', [CategoryController::class, 'create'])->middleware('api.admin')->name('category.create');
    Route::post('/categories/{categoryId}/attach-product', [CategoryController::class, 'attachProduct'])->middleware('api.admin');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->middleware('api.admin')->name('category.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware('api.admin')->name('category.delete');

    Route::get('/products-types', [ProductTypeController::class, 'getData'])->middleware('api.admin')->name('type.getData');
    Route::post('/products-type', [ProductTypeController::class, 'create'])->middleware('api.admin')->name('type.create');

    Route::get('/order', [OrderController::class, 'index'])->middleware('api.admin');
    Route::post('/order/confirm', [OrderController::class, 'comfirm'])->middleware('api.admin');
    Route::post('/order/next-status/{id}', [OrderController::class, 'nextStatus'])->middleware('api.admin');
    Route::delete('/order/{id}', [OrderController::class, 'destroy'])->middleware('api.admin');

    Route::post('/size', [SizeController::class, 'create'])->middleware('api.admin')->name('size.create');
    Route::get('/sizes/{product_type_id}', [SizeController::class, 'getSizesByProductTypeId'])->middleware('api.admin');

    Route::get('/colors', [ColorController::class, 'getData'])->middleware('api.admin')->name('color.getData');
    Route::post('/color', [ColorController::class, 'create'])->middleware('api.admin')->name('color.create');

    Route::get('/tags', [TagController::class, 'getData'])->middleware('api.admin')->name('tag.getData');
    Route::post('/tag', [TagController::class, 'create'])->middleware('api.admin')->name('tag.create');

    Route::get('/product', [ProductController::class, 'index'])->middleware('api.admin')->name('product.index');
    Route::get('/product/getAll', [ProductController::class, 'getAll'])->middleware('api.admin');
    Route::post('/product', [ProductController::class, 'create'])->middleware('api.admin')->name('product.create');
    Route::post('/product/addcolor/{id}', [ProductController::class, 'addColorFromProduct'])->middleware('api.admin')->name('product.addColor');
    Route::put('/product/updatecolor/{id}', [ProductController::class, 'updateColorInfo'])->middleware('api.admin');
    Route::post('/product/addimage/{id}', [ProductController::class, 'addImageFromProduct'])->middleware('api.admin')->name('product.addImage');
    Route::post('/product/size/{id}', [ProductController::class, 'updateSizesProduct'])->middleware('api.admin');
    Route::put('/product/{id}', [ProductController::class, 'update'])->middleware('api.admin')->name('product.updateInfo');
    Route::delete('/product/image/{id}', [ProductController::class, 'deleteImageFromProduct'])->middleware('api.admin')->name('product.deleteImage');
    Route::delete('/product/color/{id}', [ProductController::class, 'deleteColorFromProduct'])->middleware('api.admin')->name('product.deleteColor');
    Route::delete('/product/{id}', [ProductController::class, 'destroy'])->middleware('api.admin')->name('product.delete');

    Route::post('uploads/store', [UploadController::class, 'store'])->middleware('api.admin')->name('uploads.store');

    Route::get('/category-homepages', [CategoryHomepageController::class, 'index'])->middleware('api.admin');
    Route::get('/category-homepages/{id}', [CategoryHomepageController::class, 'show'])->middleware('api.admin');
    Route::post('/category-homepages', [CategoryHomepageController::class, 'store'])->middleware('api.admin');
    Route::put('/category-homepages/{id}', [CategoryHomepageController::class, 'update'])->middleware('api.admin');
    Route::delete('/category-homepages/{id}', [CategoryHomepageController::class, 'destroy'])->middleware('api.admin');

    Route::get('/banner-homepages/{id}', [BannerHomepageController::class, 'show'])->middleware('api.admin')->name('banner');
    Route::post('/banner-homepages', [BannerHomepageController::class, 'store'])->middleware('api.admin')->name('banner.create');
    Route::put('/banner-homepages/{id}', [BannerHomepageController::class, 'update'])->middleware('api.admin')->name('banner.update');
    Route::delete('/banner-homepages/{id}', [BannerHomepageController::class, 'destroy'])->middleware('api.admin')->name('banner.delete');
    Route::get('/reviews/all', [ReviewController::class, 'getAll'])->middleware('api.admin');

    Route::post('/reply', [ReplyController::class, 'store'])->middleware('api.admin');
    
    //user 
    Route::post('/order/cancle/{id}', [OrderController::class, 'cancelOrder']);
    Route::post('/order', [OrderController::class, 'store'])->name('order.store');
    Route::get('/order/get_all', [OrderController::class, 'getByUserId'])->name('order.getByUserId');
    Route::get('/order/get_all_review', [OrderController::class, 'getAllOrderReviews'])->name('order.getAllOrderReviews');
    Route::get('/order/{id}', [OrderController::class, 'show'])->name('order.show');
    Route::get('/test', [OrderController::class, 'test'])->name('test.api');

    Route::delete('/reviews/user/{id}', [ReviewController::class, 'destroyFromUser']);
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/getByUserId', [ReviewController::class, 'getByUserId'])->name('reviews.getByUserId');
    Route::get('/reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::put('/reviews/{id}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->middleware('api.admin')->name('reviews.destroy');

    Route::post('uploads/storeImagesReview', [UploadController::class, 'storeReviewImage'])->name('uploads.storeReviewImage');
    Route::post('uploads/destroy', [UploadController::class, 'destroy'])->name('uploads.destroy');


    Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout.api');
});

Route::group(['middleware' => ['cors', 'json.response']], function () {
    Route::get('/product/{id}', [ProductController::class, 'getProduct']);
});