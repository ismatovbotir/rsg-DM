<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): void
    {
        User::factory()->create(['email' => 'pda@rsg.uz', 'role' => 'user']);
        User::factory()->create(['email' => 'admin@rsg.uz', 'role' => 'admin']);
    }

    public function test_disabled_outside_local_env(): void
    {
        $this->seedUsers();
        config(['dev.autologin' => true]); // env is "testing"

        $this->get('/')->assertOk()->assertSee('type="password"', false)->assertDontSee('Bosh menyu');
        $this->get('/dev/login/admin')->assertNotFound();
    }

    public function test_autologin_and_switching_in_local_env(): void
    {
        $this->seedUsers();
        config(['dev.autologin' => true, 'dev.default' => 'user']);
        $this->app['env'] = 'local';

        $this->get('/')->assertOk();
        $this->assertSame('pda@rsg.uz', auth()->user()->email);

        $this->get('/admin/documents')->assertForbidden();

        auth()->logout();
        $this->get('/dev/login/admin')->assertRedirect(route('admin.documents.index'));
        $this->assertSame('admin@rsg.uz', auth()->user()->email);
    }

    public function test_explicit_logout_stays_logged_out(): void
    {
        $this->seedUsers();
        config(['dev.autologin' => true]);
        $this->app['env'] = 'local';

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class); // CSRF is enforced once env != testing

        $this->get('/')->assertOk();
        $this->post('/logout')->assertCookie('dev_noauto');
    }
}
