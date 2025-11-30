<?php

namespace App\Services;

use Aws\S3\S3Client;
use Exception;

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
}
