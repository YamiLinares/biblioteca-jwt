<?php

use Illuminate\Support\Facades\Route;

Route::view('/docs', 'docs')->name('docs');
Route::get('/postman/collection', fn () => response()->download(base_path('docs/postman/Biblioteca-JWT.postman_collection.json')));
Route::view('/{path?}', 'app')->where('path', '^(?!api|docs).*$')->name('app');
