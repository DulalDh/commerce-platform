<?php

use App\Modules\CMS\Http\Controllers\BlogPostController;
use App\Modules\CMS\Http\Controllers\FaqController;
use App\Modules\CMS\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/cms/pages', [PageController::class, 'public']);
Route::get('/cms/blog-posts', [BlogPostController::class, 'public']);
Route::get('/cms/faqs', [FaqController::class, 'public']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/pages', [PageController::class, 'index']);
    Route::post('/admin/pages', [PageController::class, 'store']);
    Route::get('/admin/pages/{page}', [PageController::class, 'show']);
    Route::put('/admin/pages/{page}', [PageController::class, 'update']);
    Route::get('/admin/pages/{page}/versions', [PageController::class, 'versions']);
    Route::delete('/admin/pages/{page}', [PageController::class, 'destroy']);

    Route::get('/admin/blog-posts', [BlogPostController::class, 'index']);
    Route::post('/admin/blog-posts', [BlogPostController::class, 'store']);
    Route::get('/admin/blog-posts/{blogPost}', [BlogPostController::class, 'show']);
    Route::put('/admin/blog-posts/{blogPost}', [BlogPostController::class, 'update']);
    Route::delete('/admin/blog-posts/{blogPost}', [BlogPostController::class, 'destroy']);

    Route::get('/admin/faqs', [FaqController::class, 'index']);
    Route::post('/admin/faqs', [FaqController::class, 'store']);
    Route::get('/admin/faqs/{faq}', [FaqController::class, 'show']);
    Route::put('/admin/faqs/{faq}', [FaqController::class, 'update']);
    Route::delete('/admin/faqs/{faq}', [FaqController::class, 'destroy']);
});
