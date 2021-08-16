<?php

namespace Tests\Unit;

use App\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{

    /** @var User */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    // user can login and is sent redirect link
    // user gets a cookie to retain login state
    // user cannot access if doesn't verify email
    // user cannot login with a non-existent email
    // user cannot login with an incorrect password
    // user cannot attempt more than 5 logins per minute
    // user can access authenticated pages when logged in
    // user can logout and is sent redirect link
    // user cannot access authenticated pages when logged out (401)

    public function test_create_tag()
    {
        $this->actingAs($this->user)->postJson('api/tag', [
            'tag' => 'test'
        ])->dump()->assertSuccessful();
    }
}
