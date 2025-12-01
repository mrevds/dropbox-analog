<?php

namespace App\Services;

use Aws\S3\S3Client;
use Exception;
use Illuminate\Support\Facades\Log;


class S3Service
{
    protected S3Client $s3Client;
    protected string $bucket;

    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'http' => [
                'verify' => false
            ]
        ]);

        $this->bucket = env('AWS_BUCKET');
    }

    public function createUserDirectory(int $userId): bool
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => "users/{$userId}/",
                'Body'   => '',
            ]);
            return true;
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function uploadFile(int $userId, $file): ?array
    {
        Log::info('Upload started', ['user_id' => $userId, 'file' => $file->getClientOriginalName()]);

        try {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = "users/{$userId}/{$fileName}";

            Log::info('Uploading to S3', ['path' => $path]);

            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
                'Body'   => fopen($file->getRealPath(), 'rb'),
                'ContentType' => $file->getMimeType(),
            ]);

            Log::info('Upload successful', ['url' => $result['ObjectURL']]);

            return [
                'url' => $result['ObjectURL'],
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        } catch (Exception $e) {
            Log::error('Upload failed', ['error' => $e->getMessage()]);
            report($e);
            return null;
        }
    }
    public function deleteFile(string $path): bool
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
            ]);
            return true;
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    public function getSignedUrl(string $path, int $expiresInMinutes = 5): string
    {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $path,
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, "+{$expiresInMinutes} minutes");
            return (string) $request->getUri();
        } catch (Exception $e) {
            Log::error('Failed to generate signed URL', ['error' => $e->getMessage(), 'path' => $path]);
            report($e);
            throw $e;
        }
    }
}
