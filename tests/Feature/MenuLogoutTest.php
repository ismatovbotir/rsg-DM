<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_page_has_a_visible_logout_button(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/')->assertOk()->assertSee('Chiqish')->assertSee('action="'.route('logout').'"', false);
        $this->actingAs($user)->post('/logout')->assertRedirect();
        $this->assertGuest();
    }
}
