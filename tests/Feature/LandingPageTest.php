<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_public_landing_page_loads_with_sign_in_and_get_started(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('RadiusPoint')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }
}
