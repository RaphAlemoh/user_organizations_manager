<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Organization;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    use ApiResponser;

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'userId' => uniqid(),
            'firstName' => $validated['firstName'],
            'lastName' => $validated['lastName'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
        ]);

        $organization = Organization::create([
            'orgId' => uniqid(),
            'name' => $user->firstName . "'s Organization",
            'description' => 'Default organization for ' . $user->firstName,
        ]);

        $user->organizations()->attach($organization->id);

        $token = JWTAuth::fromUser($user);

        return ($user == true) ?
            $this->jsonReponse([
                'status' => 'success',
                'message' => 'Registration successful',
                'data' => [
                    'accessToken' => $token,
                    'user' => [
                        'userId' => $user->userId,
                        'firstName' => $user->firstName,
                        'lastName' => $user->lastName,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                ],
            ], Response::HTTP_CREATED)
            : $this->jsonReponse([
                'status' => "Bad request",
                "message" => "Registration unsuccessful",
                "statusCode" => 400
            ], Response::HTTP_BAD_REQUEST);
    }


    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (!JWTAuth::attempt($validated)) {
            return $this->jsonReponse([
                'status' => "Incorrect credentials",
                "message" => "Authentication failed",
                "statusCode" => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }
        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        $token = JWTAuth::fromUser($user);
        return $this->jsonReponse([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'accessToken' => $token,
                'user' => [
                    'userId' => $user->userId,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ],
        ], Response::HTTP_OK);
    }
}
