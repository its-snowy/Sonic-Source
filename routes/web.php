<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MusicRecognitionController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/', [MusicRecognitionController::class, 'index'])->name('home');
Route::post('/recognize', [MusicRecognitionController::class, 'recognize'])->name('recognize');
