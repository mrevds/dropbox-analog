<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Storage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::get('/test-s3', function () {
    $config = [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
    ];

    Storage::build($config)->put('test-direct.txt', 'Direct upload');

    return 'Uploaded!';
});

Route::get('/debug-s3', function () {
    try {
        // Проверяем конфигурацию
        dump('Bucket:', env('AWS_BUCKET'));
        dump('Region:', env('AWS_DEFAULT_REGION'));
        dump('Key length:', strlen(env('AWS_ACCESS_KEY_ID')));
        dump('Secret length:', strlen(env('AWS_SECRET_ACCESS_KEY')));

        // Пробуем записать файл
        $result = Storage::disk('s3')->put('debug-test.txt', 'test content');
        dump('Success:', $result);

    } catch (Exception $e) {
        dump('Error:', $e->getMessage());
    }
});

Route::get('/s3-head', function () {
    try {
        $result = Storage::disk('s3')->get('hello.txt');
        return [
            'exists' => true,
            'content' => $result,
        ];
    } catch (\Throwable $e) {
        return [
            'exists' => false,
            'error' => $e->getMessage(),
        ];
    }
});


Route::post('/logout', [UserController::class, 'logout']);


Route::get('/check-aws', function () {
    // Проверим, правильно ли читаются credentials
    $key = env('AWS_ACCESS_KEY_ID');
    $secret = env('AWS_SECRET_ACCESS_KEY');
    $bucket = env('AWS_BUCKET');
    $region = env('AWS_DEFAULT_REGION');

    dump('Key:', $key);
    dump('Secret starts with:', substr($secret, 0, 5) . '...');
    dump('Bucket:', $bucket);
    dump('Region:', $region);

    // Проверим, есть ли доступ к бакету через AWS SDK напрямую
    $s3Client = new \Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => $region,
        'credentials' => [
            'key'    => $key,
            'secret' => $secret,
        ],
    ]);

    try {
        // Пробуем получить список бакетов
        $buckets = $s3Client->listBuckets();
        dump('Available buckets:', $buckets['Buckets']);

        // Пробуем записать файл напрямую через SDK
        $result = $s3Client->putObject([
            'Bucket' => $bucket,
            'Key'    => 'sdk-direct-test.txt',
            'Body'   => 'Hello from AWS SDK!',
        ]);
        dump('SDK upload success:', $result['ObjectURL']);

    } catch (\Aws\Exception\AwsException $e) {
        dump('AWS Error:', $e->getMessage());
        dump('AWS Error Code:', $e->getAwsErrorCode());
    } catch (Exception $e) {
        dump('General Error:', $e->getMessage());
    }
});


Route::get('/test-s3-operations', function () {
    $operations = [];

    try {
        // 1. Проверяем, существует ли бакет
        $operations['bucket_exists'] = Storage::disk('s3')->getDriver()->getAdapter()->getClient()->doesBucketExist(env('AWS_BUCKET'));

        // 2. Пробуем листинг (требует s3:ListBucket)
        $operations['list_files'] = Storage::disk('s3')->files('/');

        // 3. Пробуем запись с разными параметрами
        $operations['put_plain'] = Storage::disk('s3')->put('test1.txt', 'content1');
        $operations['put_with_visibility'] = Storage::disk('s3')->put('test2.txt', 'content2', 'public');

    } catch (Exception $e) {
        $operations['error'] = $e->getMessage();
    }

    dump($operations);
});

Route::get('/test-s3-no-ssl', function () {
    $s3Client = new \Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => env('AWS_DEFAULT_REGION'),
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
        'http' => [
            'verify' => false // Отключает SSL verification
        ]
    ]);

    try {
        $result = $s3Client->putObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key'    => 'wow.txt',
            'Body'   => 'Testing without SSL verification',
        ]);
        dump('Success:', $result['ObjectURL']);
    } catch (Exception $e) {
        dump('Error:', $e->getMessage());
    }
});
