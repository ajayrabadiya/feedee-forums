<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'username' => 'maya_bakes',
            'email' => 'maya@example.com',
            'password' => 'secret-password-1',
            'password_confirmation' => 'secret-password-1',
            'terms' => '1',
            ...$overrides,
        ];
    }

    public function test_visitor_can_register_as_a_customer(): void
    {
        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'maya@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('maya_bakes', $user->username);
        $this->assertNotSame('secret-password-1', $user->password);
        $this->assertTrue(Hash::check('secret-password-1', $user->password));
    }

    public function test_submitted_role_is_ignored_during_registration(): void
    {
        $this->post(route('register.store'), $this->validPayload(['role' => 'admin', 'is_admin' => 1]));

        $user = User::where('email', 'maya@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_registration_requires_unique_username_and_email(): void
    {
        User::factory()->create(['username' => 'maya_bakes', 'email' => 'maya@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), $this->validPayload([
            'email' => 'MAYA@example.com',
        ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['username', 'email']);
        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    public function test_registration_validates_fields(): void
    {
        $response = $this->post(route('register.store'), [
            'username' => 'a b',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['username', 'email', 'password', 'terms']);
        $this->assertGuest();
    }

    public function test_registration_returns_visitor_to_the_page_they_were_viewing(): void
    {
        $this->get(route('register', ['redirect' => '/rules']))->assertOk();

        $response = $this->post(route('register.store'), $this->validPayload());

        $response->assertRedirect(url('/rules'));
    }

    public function test_authenticated_user_is_redirected_away_from_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('register'))->assertRedirect(route('dashboard'));
    }
}
