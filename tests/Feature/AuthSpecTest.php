<?php

namespace Tests;

use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthSpecTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_user_successfully_with_default_organisation()
    {
        $userData = [
            'firstName' => 'alems',
            'lastName' => 'baja',
            'email' => 'alems@hng5.com',
            'password' => 'hng-stage-2',
        ];

        $response = $this->json('post', 'api/auth/register', $userData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'accessToken',
                    'user' => [
                        'userId',
                        'firstName',
                        'lastName',
                        'email',
                        'phone',  // Assuming phone is nullable
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', ['email' => $userData['email']]);

        $this->assertDatabaseHas('organizations', [
            'name' => $userData['firstName'] . "'s Organization",
        ]);
    }

    public function test_failure_when_register_with_missing_fields()
    {
        $response = $this->json('post', 'api/auth/register', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'field',
                        'message'
                    ],
                ]
            ]);
    }

    public function test_it_fails_for_duplicate_email_registration()
    {
        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $userData = [
            'firstName' => 'alems',
            'lastName' => 'baja',
            'email' => $user->email,
            'password' => 'hng-stage-2',
        ];

        $response = $this->json('post', 'api/auth/register', $userData);


        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'field',
                        'message'
                    ],
                ]
            ]);
    }

    public function test_it_contains_correct_token()
    {

        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $token = JWTAuth::fromUser($user);

        $this->assertNotNull($token);
    }

    public function test_successful_login()
    {

        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task22.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);


        $login = [
            'email' => $user->email,
            'password' => 'password',
        ];

        $response = $this->json('post', 'api/auth/login', $login);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'accessToken',
                    'user' => [
                        'userId',
                        'firstName',
                        'lastName',
                        'email',
                        'phone',
                    ],
                ],
            ]);

        $this->assertAuthenticated();
    }

    public function test_failed_login()
    {

        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@hng1.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $login = [
            'email' => $user->email,
            'password' => 'passworddd',
        ];

        $response = $this->json('post', 'api/auth/login', $login);


        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonStructure([
                "status",
                "message",
                "statusCode"
            ]);

        $this->assertGuest();
    }

    public function check_login_fields_validation_and_response()
    {
        $login = [
            'email' => 'dummy@fail.com',
            'passwordd' => 'password',
        ];

        $response = $this->json('post', 'api/auth/login', $login);


        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'field',
                        'message'
                    ],
                ]
            ]);
    }

    public function test_the_token_expiration()
    {

        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $token = JWTAuth::fromUser($user);

        config(['jwt.ttl' => 60]);

        // Add a week, one hour and one minute to the token
        Carbon::setTestNow(Carbon::now()->addHour()->addMinute());

        try {
            JWTAuth::setToken($token)->authenticate();
            $this->assertFalse(false, 'The token is still active.');
        } catch (TokenExpiredException $e) {
            $this->assertTrue(true, 'The token should be expired.');
        }
    }

    public function test_get_authenticated_user_with_active_token()
    {
        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->json('get', 'api/users/' . $user->userId);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'userId',
                    'firstName',
                    'lastName',
                    'email',
                    'phone',
                ],
            ]);
    }


    public function test_user_cannot_view_organisation_they_do_not_belong_to()
    {
        $user1 = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec1_hng@task1.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $organization = Organization::create([
            'orgId' => uniqid(),
            'name' => $user1->firstName . "'s  EC2 HNG",
            'description' => "Some description here" ?? null,
        ]);

        $user1->organizations()->attach($organization);

        $user2 = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);
        $token2 = JWTAuth::fromUser($user2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->json('get', 'api/organisations/' . $organization->orgId);

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ]);
    }


    public function test_user_can_view_organisation_they_belong_to()
    {


        $user = User::create([
            'userId' => uniqid(),
            'firstName' => 'Alems_hng',
            'lastName' => 'Baja',
            'email' => 'ec2_hng@task2.com',
            'password' => Hash::make('password'),
            'phone' => '+23404998322',
        ]);

        $organization = Organization::create([
            'orgId' => uniqid(),
            'name' => $user->firstName . "'s  EC2 HNG",
            'description' => "Some description here" ?? null,
        ]);

        $user->organizations()->attach($organization);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->json('get', 'api/organisations/' . $organization->orgId);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'orgId',
                    'name',
                    'description'
                ]
            ]);
    }
}
