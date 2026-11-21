<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    private function latestEmailToken(): string
    {
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $body = $messages->last()->getOriginalMessage()->getTextBody();
        $this->assertSame(1, preg_match('/#([A-Za-z0-9]{64})/', $body, $m));

        return $m[1];
    }

    private function latestEmailOtp(): string
    {
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $body = $messages->last()->getOriginalMessage()->getTextBody();
        $this->assertSame(1, preg_match('/sign-in code is: ([0-9]{6})/', $body, $m));

        return $m[1];
    }

    public function test_registration_email_code_expiry_replay_and_logout(): void
    {
        $data = ['name' => 'Test Student', 'email' => ' New@Example.Test ', 'password' => 'long-demo-password', 'password_confirmation' => 'long-demo-password'];
        $this->post('/register', $data + ['role' => 'admin', 'balance' => 9000000])->assertRedirect('/login');
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertSame(100000, (int) $user->balance);
        $this->assertNull($user->email_verified_at);
        $this->post('/login', $data)->assertSessionMissing('challenge');
        $this->assertGuest();
        $token = $this->latestEmailToken();
        $this->get('/verify/'.$user->id)->assertOk();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->post('/verify/'.$user->id, ['token' => $token])->assertRedirect('/login');
        $this->post('/verify/'.$user->id, ['token' => $token])->assertSessionHasErrors('email');
        $this->post('/login', $data)->assertRedirect('/email-code');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'login_otp_expires' => now()->addSeconds(60)->format('Y-m-d H:i:s')]);
        $code = $this->latestEmailOtp();
        $this->get('/email-code')->assertOk()->assertSee('expires after 60 seconds');
        $before = session()->getId();
        $this->post('/email-code', ['code' => $code, 'user_id' => $this->user('admin')->id])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'login_otp_hash' => null]);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->assertFalse(session()->has('mfa_user'));

        $this->post('/login', $data)->assertRedirect('/email-code');
        $this->post('/email-code', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_email_code_expiry_and_request_attempt_lockout(): void
    {
        $user = $this->user();
        $data = ['email' => $user->email, 'password' => 'SecureShop-Demo-431'];
        $this->post('/login', $data)->assertRedirect('/email-code');
        $wrong = $this->latestEmailOtp() === '000000' ? '000001' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/email-code', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->assertNotNull($user->fresh()->login_otp_locked_until);
        $this->post('/login', $data)->assertSessionHasErrors('email');

        $this->withSession(['challenge' => ['user' => $user->id, 'nonce' => 'expired', 'expires' => time() - 1]])
            ->post('/email-code', ['code' => '123456'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_missing_or_expired_session_cannot_complete_code(): void
    {
        $this->post('/email-code', ['code' => '123456'])->assertRedirect('/login');
        $this->withSession(['challenge' => ['user' => $this->user()->id, 'nonce' => 'expired', 'expires' => time() - 1]])
            ->post('/email-code', ['code' => '123456'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_changed_email_requires_ownership_and_profile_is_allowlisted(): void
    {
        $u = $this->signIn();
        $this->get('/account')->assertOk();
        $this->patch('/account', ['name' => 'New name', 'email' => ' New@Example.Test ', 'role' => 'admin', 'balance' => 1])->assertSessionHasNoErrors();
        $this->assertSame('customer@secureshop.test', $u->fresh()->email);
        $this->assertSame('new@example.test', $u->fresh()->pending_email);
        $this->assertSame('customer', $u->fresh()->role);
        $this->assertSame(100000, (int) $u->fresh()->balance);
        $this->post('/verify/'.$u->id, ['token' => $this->latestEmailToken()])->assertRedirect('/account');
        $this->assertSame('new@example.test', $u->fresh()->email);
        $this->assertNull($u->fresh()->pending_email);
    }

    public function test_admin_authorization_and_deactivation(): void
    {
        $this->get('/cart')->assertRedirect('/login');
        $customer = $this->signIn();
        $this->get('/admin')->assertForbidden();
        $this->patch('/admin/users/'.$customer->id, ['active' => 0])->assertForbidden();
        $this->signIn('admin');
        foreach (['products', 'users', 'coupons'] as $section) {
            $this->get('/admin/'.$section)->assertOk();
        }
        $this->patch('/admin/users/'.$customer->id, ['active' => 0])->assertSessionHasNoErrors();
        $this->actingAs($customer->fresh())->withSession(['mfa_user' => $customer->id]);
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }
}
