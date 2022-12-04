<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TweetController;

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

// ルーティング　タイムラインからRTを実施
Route::get('/timeline', [TweetController::class, 'rtFromTimeLine']);

// ルーティング 検索キーワードからRTを実施
Route::get('/search', [TweetController::class, 'rtFromSearch']);

// ルーティング トレンドの関連キーワードからRTを実施
Route::get('/trend', [TweetController::class, 'rtFromTrend']);