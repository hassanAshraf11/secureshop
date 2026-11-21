<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BrowserControlsTest extends TestCase
{
    public function test_browser_post_requires_csrf(): void
    {
        // Enable the real CSRF check normally bypassed by Laravel in its test environment.
        $this->app['env'] = 'local';
        $this->post('/newsletter', ['email' => 'hello@example.test'])->assertStatus(419);
        $this->assertDatabaseMissing('coupons', ['subscriber_email' => 'hello@example.test']);
        $token = session()->token();
        $this->withSession(['_token' => $token])->post('/newsletter', [
            '_token' => $token, 'email' => 'hello@example.test',
        ])->assertRedirect();
        $this->assertDatabaseHas('coupons', ['subscriber_email' => 'hello@example.test']);
    }

    public function test_admin_edits_and_blade_text_escaping(): void
    {
        $this->signIn('admin');
        $p = $this->keyboard();
        $name = 'Desk <Studio> & Co';
        $this->patch('/admin/products/'.$p->id, ['name' => $name, 'description' => 'Desk essential', 'category' => 'Keyboards',
            'egp' => '499.99', 'stock' => 5, 'active' => 1])->assertSessionHasNoErrors();
        $this->get('/products/'.$p->id)->assertOk()->assertSee($name)->assertDontSee($name, false)->assertSee('499.99 EGP');
        $this->assertDatabaseHas('products', ['id' => $p->id, 'price' => 49999, 'stock' => 5]);
        $this->post('/admin/coupons', ['code' => 'desk5', 'percent' => 5, 'expires_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'max_uses' => 2, 'active' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('coupons', ['code' => 'DESK5', 'percent' => 5, 'max_uses' => 2]);
        $id = DB::table('coupons')->where('code', 'DESK5')->value('id');
        $this->patch('/admin/coupons/'.$id, ['percent' => 8, 'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'max_uses' => 4, 'active' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('coupons', ['id' => $id, 'percent' => 8, 'max_uses' => 4, 'active' => 0]);
    }
}
