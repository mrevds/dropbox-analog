<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\FileController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

//// Тестовый роут для проверки upload-multiple
//Route::middleware('auth:sanctum')->post('/test-upload-multiple', function (Request $request) {
//    return response()->json([
//        'message' => 'Test endpoint reached',
//        'has_files' => $request->hasFile('files'),
//        'has_files_' => $request->hasFile('files_'),
//        'all_files' => $request->allFiles(),
//        'all_keys' => array_keys($request->all()),
//        'user_id' => auth()->id(),
//    ]);
//});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/files/upload', [FileController::class, 'upload']);
    Route::post('/files/upload-multiple', [FileController::class, 'uploadMultiple']);
    Route::get('/files', [FileController::class, 'index']); // список файлов юзера
    Route::get('/files/{id}/download', [FileController::class, 'download']); // скачать файл
    Route::delete('/files/{id}', [FileController::class, 'delete']); // удалить файл
    Route::post('/logout', [UserController::class, 'logout']);
});
