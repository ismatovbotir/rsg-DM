<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_requires_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Badge QR kodni skanerlang')->assertDontSee('Bosh menyu');
    }
}
