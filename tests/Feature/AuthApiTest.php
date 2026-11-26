<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_returns_a_real_jwt(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ana Rivera',
            'email' => 'ana@example.test',
            'password' => 'Biblioteca2026!',
            'password_confirmation' => 'Biblioteca2026!',
            'is_admin' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('expires_in', 3600)
            ->assertJsonPath('user.email', 'ana@example.test')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.is_admin');

        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Biblioteca2026!', $user->password));
        $this->assertNotSame('Biblioteca2026!', $user->password);
        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$response->json('access_token')])
            ->assertOk()
            ->assertJsonPath('data.email', 'ana@example.test')
            ->assertJsonMissingPath('data.password');
    }

    public function test_registration_rejects_duplicate_email_and_weak_password(): void
    {
        User::factory()->create(['email' => 'registered@example.test']);

        $this->postJson('/api/auth/register', $this->registrationPayload(['email' => 'registered@example.test']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/auth/register', $this->registrationPayload(['email' => 'invalid-email']))
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/auth/register', $this->registrationPayload(['password' => 'weak', 'password_confirmation' => 'weak']))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/auth/register', $this->registrationPayload(['password_confirmation' => 'Different2026!']))
            ->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_login_returns_jwt_for_valid_credentials_and_generic_401_for_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'reader@example.test', 'password' => 'ReaderPassword2026!']);

        $this->postJson('/api/auth/login', ['email' => 'reader@example.test', 'password' => 'ReaderPassword2026!'])
            ->assertOk()
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('expires_in', 3600)
            ->assertJsonMissingPath('user.password');

        $this->postJson('/api/auth/login', ['email' => 'reader@example.test', 'password' => 'WrongPassword2026!'])
            ->assertUnauthorized()->assertJsonPath('message', 'Credenciales incorrectas.');
        $this->postJson('/api/auth/login', ['email' => 'missing@example.test', 'password' => 'WrongPassword2026!'])
            ->assertUnauthorized()->assertJsonPath('message', 'Credenciales incorrectas.');
    }

    public function test_profile_and_logout_require_and_revoke_the_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/auth/me', $headers)->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/auth/logout', [], $headers)
            ->assertOk()->assertJsonPath('message', 'La sesión se cerró correctamente.');
        $this->getJson('/api/auth/me', $headers)->assertUnauthorized();
        $this->postJson('/api/auth/logout', [], $headers)->assertUnauthorized();
    }

    public function test_authentication_rejects_missing_malformed_and_tampered_tokens(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized()->assertJsonPath('message', 'No autenticado.');
        $this->getJson('/api/books', ['Authorization' => 'Bearer not.a.jwt'])->assertUnauthorized();

        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);
        $parts = explode('.', $token);
        $parts[1] = ($parts[1][0] === 'e' ? 'f' : 'e').substr($parts[1], 1);
        $tampered = implode('.', $parts);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/books', ['Authorization' => 'Bearer '.$tampered])->assertUnauthorized();
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);
        $this->app['auth']->forgetGuards();

        Carbon::setTestNow(Carbon::now('UTC')->addMinutes(61));
        try {
            $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_token_signed_with_a_different_secret_is_rejected(): void
    {
        $user = User::factory()->create();
        $provider = app('tymon.jwt')->getJWTProvider();
        $applicationSecret = config('jwt.secret');
        $provider->setSecret(str_repeat('x', 64));
        $tokenSignedWithOtherSecret = app('tymon.jwt')->fromUser($user);
        $provider->setSecret($applicationSecret);

        $this->getJson('/api/auth/me', ['Authorization' => 'Bearer '.$tokenSignedWithOtherSecret])->assertUnauthorized();
    }

    public function test_authentication_endpoints_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'UnknownPassword2026!'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => 'unknown@example.test', 'password' => 'UnknownPassword2026!'])
            ->assertStatus(429);
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Lectora de prueba',
            'email' => 'new-reader@example.test',
            'password' => 'Biblioteca2026!',
            'password_confirmation' => 'Biblioteca2026!',
        ], $overrides);
    }
}
