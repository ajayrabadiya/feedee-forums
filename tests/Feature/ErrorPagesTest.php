<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_admin_follows_login_flow(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_customer_gets_branded_403_page_with_real_status(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/admin/posts');

        $response->assertForbidden();
        $response->assertSee('<main id="main" class="container error-page" aria-labelledby="errorTitle">', false);
        $response->assertSee('<div class="error-code" aria-hidden="true">403</div>', false);
        $response->assertSee('Access Denied');
        $response->assertSee("You don't have permission to access this page.", false);
        $response->assertSee('Go to Homepage');
        $response->assertSee('href="'.route('home').'"', false);
        $response->assertSee(asset('assets/css/style.css'), false);
        $response->assertSee(asset('assets/images/logo.png'), false);
        $response->assertDontSee('Waiting for review');
        $response->assertDontSee('Log In</a>', false);
    }

    public function test_403_page_does_not_leak_internal_details(): void
    {
        config(['app.debug' => true]);

        $response = $this->actingAs(User::factory()->create())->get('/admin');

        $response->assertForbidden();
        foreach (['Stack trace', 'vendor', 'SQLSTATE', 'Symfony\\', 'EnsureUserHasRole', 'APP_KEY', base_path()] as $secret) {
            $response->assertDontSee($secret, false);
        }
        $response->assertDontSee(route('admin.posts'), false);
    }

    public function test_go_back_only_links_to_same_site_pages(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->withHeader('referer', 'https://evil.example.com/phish')
            ->get('/admin')
            ->assertForbidden()
            ->assertDontSee('evil.example.com')
            ->assertDontSee('Go Back');

        $this->actingAs($customer)
            ->withHeader('referer', url('/rules'))
            ->get('/admin')
            ->assertForbidden()
            ->assertSee('href="'.url('/rules').'"', false)
            ->assertSee('Go Back');
    }

    public function test_guest_seeing_403_gets_a_login_link_back_to_the_page(): void
    {
        Route::middleware('web')->get('/_test/forbidden', fn () => abort(403));

        $this->get('/_test/forbidden')
            ->assertForbidden()
            ->assertSee('Access Denied')
            ->assertSee(route('login', ['redirect' => '/_test/forbidden']), false);
    }

    public function test_admin_can_still_access_admin_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee('Waiting for review');
    }

    public function test_json_requests_still_receive_json_403(): void
    {
        $response = $this->actingAs(User::factory()->create())->getJson('/admin');

        $response->assertForbidden();
        $response->assertHeader('content-type', 'application/json');
        $response->assertJsonStructure(['message']);
        $this->assertStringNotContainsString('Access Denied', $response->getContent());
    }

    public function test_json_403_hides_internals_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $this->actingAs(User::factory()->create())
            ->getJson('/admin')
            ->assertForbidden()
            ->assertExactJson(['message' => 'You do not have permission to access this resource.']);
    }

    public function test_other_error_pages_still_work(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('This post has floated away');
        $this->getJson('/this-page-does-not-exist')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_validation_errors_are_unaffected(): void
    {
        $this->post(route('login.store'), [])->assertSessionHasErrors(['login', 'password']);
    }

    public function test_oversized_request_renders_friendly_413_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->call(
            'POST',
            route('posts.store'),
            server: ['CONTENT_LENGTH' => (string) (100 * 1024 * 1024 * 1024)],
        );

        $response->assertStatus(413);
        $response->assertSee('<div class="error-code" aria-hidden="true">413</div>', false);
        $response->assertSee('That upload was too large');
    }
}
