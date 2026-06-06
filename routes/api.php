<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HeaderAnalyzerController;

Route::post('/analyze', [HeaderAnalyzerController::class, 'analyze']);