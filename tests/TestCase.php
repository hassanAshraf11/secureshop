<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as FrameworkTest;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends FrameworkTest
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function user(string $alias = 'customer'): User
    {
        return User::where('email', $alias.'@secureshop.test')->firstOrFail();
    }

    protected function signIn(string $alias = 'customer'): User
    {
        $user = $this->user($alias);
        $this->actingAs($user)->withSession(['mfa_user' => $user->id]);

        return $user;
    }

    protected function keyboard()
    {
        return DB::table('products')->where('category', 'Keyboards')->firstOrFail();
    }

    protected function cartTicket(): string
    {
        return $this->get('/checkout')->assertOk()->viewData('ticket');
    }
}
