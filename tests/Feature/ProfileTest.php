<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_the_signed_in_users_details(): void
    {
        $user = User::factory()->create(['username' => 'maya_bakes', 'email' => 'maya@example.com']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('maya_bakes');
        $response->assertSee('maya@example.com');
    }

    public function test_customer_can_update_permitted_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maya B.',
            'email' => 'new-address@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Maya B.', $user->name);
        $this->assertSame('new-address@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_customer_cannot_change_role_username_or_password_through_profile_update(): void
    {
        $user = User::factory()->create(['username' => 'maya_bakes']);
        $originalPassword = $user->password;

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maya',
            'email' => $user->email,
            'role' => 'admin',
            'username' => 'hijacked',
            'password' => 'new-password-1',
            'id' => 999,
        ]);

        $user->refresh();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame('maya_bakes', $user->username);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_profile_update_only_affects_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Other Person']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Changed',
            'email' => $user->email,
            'user_id' => $otherUser->id,
        ]);

        $this->assertSame('Other Person', $otherUser->fresh()->name);
        $this->assertSame('Changed', $user->fresh()->name);
    }

    public function test_email_must_be_unique(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'Maya', 'email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }
}
