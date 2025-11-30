<?php

namespace App\Repos;

use App\Models\User;
use App\Services\S3Service;

class UserRepo {
    public function __construct(protected S3Service $s3Service){}

    public function create(array $data)
    {
        $user = User::create($data);
        $this->s3Service->createUserDirectory($user->id);
        return $user;
    }
    public function findByName(string $username)
    {
        return User::where('username', $username)->first();
    }

}
