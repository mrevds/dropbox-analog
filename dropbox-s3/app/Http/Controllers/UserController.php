<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;


class UserController extends Controller
{
    //
    public function __construct(
        private UserService $userService
    ){}

    public function register(Request $request)
    {
        $data = [
            'username' =>  $request->input('username'),
            'password' => $request->input('password')
        ];
        $this->userService->createUser($data);
        return response()->json("User created");
    }
    public function login(Request $request)
    {
        $user = $this->userService->login(
            $request->input('username'),
            $request->input('password')
        );
        $token = $user->createToken('auth_Token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }
}
