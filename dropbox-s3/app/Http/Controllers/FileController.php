<?php

namespace App\Http\Controllers;

use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function __construct(protected S3Service $s3Service){}

    public function upload(Request $request)
    {
        Log::info('Upload endpoint hit', [
            'has_file' => $request->hasFile('file'),
            'has_file_' => $request->hasFile('file_'),
            'all_files' => $request->allFiles(),
            'all_input' => $request->all(),
            'user_id' => auth()->id(),
        ]);

        $fileField = $request->hasFile('file') ? 'file' : 'file_';

        $request->validate([
            $fileField => 'required|file|max:102400' // 100MB
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        Log::info('Validation passed, uploading file', ['field' => $fileField]);

        $fileData = $this->s3Service->uploadFile($user->id, $request->file($fileField));

        if ($fileData) {
            Log::info('File uploaded to S3, saving to DB', $fileData);

            $file = $user->files()->create([
                'name' => $fileData['name'],
                'path' => $fileData['path'],
                'url' => $fileData['url'],
                'size' => $fileData['size'],
                'mime_type' => $fileData['mime'],
            ]);

            return response()->json($file, 201);
        }

        Log::error('Upload failed - S3Service returned null');
        return response()->json(['error' => 'Upload failed'], 500);
    }

    public function uploadMultiple(Request $request)
    {
        // Прямая запись в файл для отладки
        $debugFile = storage_path('logs/upload-debug.txt');
        file_put_contents($debugFile, "\n\n=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
        file_put_contents($debugFile, "Endpoint hit!\n", FILE_APPEND);
        file_put_contents($debugFile, "User ID: " . auth()->id() . "\n", FILE_APPEND);

        // Проверяем ВСЕ возможные варианты имен полей
        $allFiles = $request->allFiles();
        file_put_contents($debugFile, "All files keys: " . json_encode(array_keys($allFiles)) . "\n", FILE_APPEND);
        file_put_contents($debugFile, "All files structure: " . json_encode($allFiles, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // Определяем какое поле используется - приоритет для массивов с []
        $filesField = null;
        $possibleFields = ['files[]', 'files', 'files_', 'file[]', 'file', 'file_'];

        foreach ($possibleFields as $field) {
            if ($request->hasFile($field)) {
                $filesField = $field;
                file_put_contents($debugFile, "Field '$field' found!\n", FILE_APPEND);
                break;
            }
        }

        // Если не нашли стандартные - ищем любое поле с файлами
        if (!$filesField) {
            foreach ($allFiles as $key => $value) {
                $filesField = $key;
                file_put_contents($debugFile, "Using first available field: '$key'\n", FILE_APPEND);
                break;
            }
        }

        if (!$filesField) {
            file_put_contents($debugFile, "ERROR: No files found in request!\n", FILE_APPEND);
            return response()->json([
                'message' => 'No files uploaded',
                'debug' => [
                    'all_files' => $allFiles,
                    'all_keys' => array_keys($request->all()),
                ]
            ], 422);
        }

        file_put_contents($debugFile, "Files field detected: $filesField\n", FILE_APPEND);

        Log::info('Upload multiple endpoint hit', [
            'files_field' => $filesField,
            'all_files' => $request->allFiles(),
            'user_id' => auth()->id(),
        ]);

        // Получаем файлы
        $rawFiles = $request->file($filesField);

        // Если это один файл (не массив) - делаем массив
        if (!is_array($rawFiles)) {
            $rawFiles = [$rawFiles];
        }

        file_put_contents($debugFile, "Files count: " . count($rawFiles) . "\n", FILE_APPEND);

        // Валидация (более гибкая)
        foreach ($rawFiles as $index => $file) {
            if (!$file->isValid()) {
                file_put_contents($debugFile, "File $index is invalid\n", FILE_APPEND);
                return response()->json([
                    'message' => "File at index $index is invalid",
                ], 422);
            }

            // Проверка размера
            if ($file->getSize() > 102400 * 1024) { // 100MB
                file_put_contents($debugFile, "File $index too large\n", FILE_APPEND);
                return response()->json([
                    'message' => "File at index $index is too large (max 100MB)",
                ], 422);
            }
        }

        file_put_contents($debugFile, "Validation passed\n", FILE_APPEND);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $uploadedFiles = [];
        $errors = [];

        foreach ($rawFiles as $index => $file) {
            file_put_contents($debugFile, "Processing file $index: " . $file->getClientOriginalName() . "\n", FILE_APPEND);

            Log::info("Processing file {$index}", [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            $fileData = $this->s3Service->uploadFile($user->id, $file);

            if ($fileData) {
                file_put_contents($debugFile, "File $index uploaded to S3: " . $fileData['url'] . "\n", FILE_APPEND);
                Log::info("File {$index} uploaded to S3 successfully");

                $uploadedFiles[] = $user->files()->create([
                    'name' => $fileData['name'],
                    'path' => $fileData['path'],
                    'url' => $fileData['url'],
                    'size' => $fileData['size'],
                    'mime_type' => $fileData['mime'],
                ]);

                file_put_contents($debugFile, "File $index saved to DB\n", FILE_APPEND);
            } else {
                file_put_contents($debugFile, "File $index FAILED to upload to S3\n", FILE_APPEND);
                Log::error("File {$index} upload failed");
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => 'Upload to S3 failed'
                ];
            }
        }

        file_put_contents($debugFile, "Completed: " . count($uploadedFiles) . " uploaded, " . count($errors) . " failed\n", FILE_APPEND);

        Log::info('Upload multiple completed', [
            'uploaded' => count($uploadedFiles),
            'failed' => count($errors),
        ]);

        if (empty($uploadedFiles) && !empty($errors)) {
            file_put_contents($debugFile, "Returning 500 - all failed\n", FILE_APPEND);
            return response()->json([
                'message' => 'All uploads failed',
                'errors' => $errors
            ], 500);
        }

        file_put_contents($debugFile, "Returning 201 - success\n", FILE_APPEND);
        return response()->json([
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
            'summary' => [
                'total' => count($rawFiles),
                'success' => count($uploadedFiles),
                'failed' => count($errors),
            ]
        ], 201);
    }

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $files = $user->files()->latest()->get();
        return response()->json($files);
    }

    public function download($id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $file = $user->files()->findOrFail($id);

        // Генерируем временную подписанную ссылку на 5 минут
        $url = $this->s3Service->getSignedUrl($file->path, 5);

        return response()->json([
            'url' => $url,
            'name' => $file->name,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
        ]);
    }

    public function delete($id)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $file = $user->files()->findOrFail($id);

        $this->s3Service->deleteFile($file->path);

        // Удаляем из БД
        $file->delete();

        return response()->json(['message' => 'File deleted']);
    }
}
