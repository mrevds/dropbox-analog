<?php

namespace App\Services;

use App\Repos\UserRepo;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService {
    public function __construct(
        private UserRepo $userRepo
    ){}

    public function createUser(array $data)
    {
        // Хешируем пароль перед сохранением
        $data['password'] = Hash::make($data['password']);
        return $this->userRepo->create($data);
    }

    public function login(string $username, string $password): ?User
    {
        $user = $this->userRepo->findByName($username);

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

}
