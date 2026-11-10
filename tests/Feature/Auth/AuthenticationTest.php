<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Register Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_register_a_new_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'ShopSphere User',
            'email' => 'shop@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'User registered successfully'
            )
            ->assertJsonPath('data.name', 'ShopSphere User')
            ->assertJsonPath('data.email', 'shop@example.com')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'name' => 'ShopSphere User',
            'email' => 'shop@example.com',
        ]);

        $user = User::where('email', 'shop@example.com')->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123', $user->password)
        );
    }

    public function test_it_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'shop@example.com',
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'shop@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_it_cannot_register_with_unconfirmed_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'ShopSphere User',
            'email' => 'shop@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'DifferentPassword123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'shop@example.com',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Login Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'shop@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'shop@example.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'User logged in successfully'
            )
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_it_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'shop@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'shop@example.com',
            'password' => 'WrongPassword123',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'Password123',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials');
    }

    /*
    |--------------------------------------------------------------------------
    | Protected Routes Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_cannot_access_protected_routes_without_token(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_it_can_access_protected_routes_with_valid_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    /*
    |--------------------------------------------------------------------------
    | Logout Tests
    |--------------------------------------------------------------------------
    */

    public function test_it_can_logout_and_revoke_current_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'User logged out successfully'
            );

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_it_cannot_use_token_after_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token');

        // Logout using the token.
        $this->withToken($token->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk();

        // Verify that the token was deleted from database.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);

        // Clear cached authentication guards.
        $this->app['auth']->forgetGuards();

        // Try accessing protected route with revoked token.
        $response = $this->withToken($token->plainTextToken)
            ->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_logout_only_revokes_current_token(): void
    {
        $user = User::factory()->create();

        $firstToken = $user->createToken('first-device');

        $secondToken = $user->createToken('second-device');

        $this->withToken($firstToken->plainTextToken)
            ->postJson('/api/logout')
            ->assertOk();

        // The current token should be deleted.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $firstToken->accessToken->id,
        ]);

        // The second device token should remain valid.
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $secondToken->accessToken->id,
        ]);

        $response = $this->withToken($secondToken->plainTextToken)
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id);
    }
}
