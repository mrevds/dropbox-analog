<?php

namespace App\Services;

use App\Repos\UserRepo;
use App\Models\User;

class UserService {
    public function __construct(
        private UserRepo $userRepo
    ){}
    public function createUser(array $data)
    {
        if (empty($data['username'])) {
            return "username is empty";
        }
        if (empty($data['password'])) {
            return "password is empty";
        }
        return $this->userRepo->create($data);
    }
    public function login(string $username, string $password): ?User
    {
        $user = $this->userRepo->findByName($username);

        if (!$user || $user->password !== $password) {
            return null;
        }

        return $user;
    }

}
