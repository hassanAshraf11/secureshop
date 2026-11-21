<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    public function test_keyboard_purchase_replay_receipt_refresh_and_owner(): void
    {
        $user = $this->signIn();
        $keyboard = $this->keyboard();
        $this->post('/cart/'.$keyboard->id, ['quantity' => 1])->assertRedirect();
        $this->post('/checkout/coupon', ['coupon' => 'WELCOME10'])->assertSessionHasNoErrors();
        $ticket = $this->cartTicket();
        $this->post('/checkout/pay', ['ticket' => $ticket, 'total' => 1])->assertStatus(303);
        $id = DB::table('orders')->value('id');
        $this->post('/checkout/pay', ['ticket' => $ticket])->assertRedirect('/orders/'.$id);
        // A receipt keeps the name and unit price captured at purchase.
        DB::table('products')->where('id', $keyboard->id)->update(['active' => false, 'name' => 'Archived model', 'price' => 10]);
        for ($i = 0; $i < 2; $i++) {
            $this->get('/orders/'.$id)->assertOk()->assertSee('450 EGP')->assertSee('550 EGP')->assertSee($keyboard->name);
        }
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME10', 'used' => 1]);
        $this->assertDatabaseHas('orders', ['id' => $id, 'subtotal' => 50000, 'discount' => 5000, 'total' => 45000, 'balance_after' => 55000]);
        $this->assertDatabaseHas('products', ['id' => $keyboard->id, 'stock' => $keyboard->stock - 1]);
        $this->assertSame(55000, (int) $user->fresh()->balance);
        $this->get('/account')->assertOk()->assertSee('Order #SS-');
        $this->signIn('second');
        $this->get('/orders/'.$id)->assertNotFound();
    }

    public function test_insufficient_credit_keeps_credit_stock_and_cart(): void
    {
        $user = $this->signIn();
        $p = $this->keyboard();
        $this->post('/cart/'.$p->id, ['quantity' => 3]);
        $review = $this->get('/checkout')->assertOk()->assertSee('Insufficient credit');
        $this->post('/checkout/pay', ['ticket' => $review->viewData('ticket')])->assertSessionHasErrors('balance');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('cart_items', ['quantity' => 3, 'user_id' => $user->id]);
        $this->assertDatabaseHas('products', ['id' => $p->id, 'stock' => $p->stock]);
        $this->assertSame(100000, (int) $user->fresh()->balance);
    }

    public function test_stock_and_price_are_checked_again_at_payment(): void
    {
        $this->signIn();
        $p = $this->keyboard();
        $this->post('/cart/'.$p->id, ['quantity' => 1]);
        $ticket = $this->cartTicket();
        DB::table('products')->where('id', $p->id)->update(['stock' => 0]);
        $this->post('/checkout/pay', ['ticket' => $ticket])->assertSessionHasErrors('stock');
        DB::table('products')->where('id', $p->id)->update(['stock' => 1, 'price' => 60000]);
        $this->post('/checkout/pay', ['ticket' => $ticket])->assertSessionHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_coupon_replacement_expiry_and_previous_redemption(): void
    {
        $this->signIn();
        $this->post('/cart/'.$this->keyboard()->id, ['quantity' => 1]);
        $this->post('/checkout/coupon', ['coupon' => 'WELCOME10'])->assertSessionHasNoErrors();
        $old = $this->cartTicket();
        $this->post('/checkout/coupon', ['coupon' => ''])->assertSessionHasNoErrors();
        $this->post('/checkout/pay', ['ticket' => $old])->assertSessionHasErrors('checkout');
        $this->post('/checkout/coupon', ['coupon' => 'WELCOME10']);
        DB::table('coupons')->where('code', 'WELCOME10')->update(['expires_at' => now()->subMinute()]);
        $this->post('/checkout/pay', ['ticket' => $old])->assertSessionHasErrors('coupon');
        $this->assertDatabaseCount('orders', 0);
        DB::table('coupons')->where('code', 'WELCOME10')->update(['expires_at' => now()->addDay()]);
        $this->post('/checkout/pay', ['ticket' => $this->cartTicket()])->assertStatus(303);
        $this->post('/cart/'.$this->keyboard()->id, ['quantity' => 1]);
        $this->post('/checkout/coupon', ['coupon' => 'WELCOME10'])->assertSessionHasErrors('coupon');
    }

    public function test_cart_is_scoped_and_cart_updates_persist(): void
    {
        $a = $this->signIn();
        $p = $this->keyboard();
        $this->post('/cart/'.$p->id, ['quantity' => 1]);
        $this->patch('/cart/'.$p->id, ['quantity' => 2])->assertSessionHasNoErrors();
        $this->get('/cart')->assertSee('1,000 EGP');
        $b = $this->signIn('second');
        $this->get('/cart')->assertSee('Your cart is empty');
        $this->delete('/cart/'.$p->id, ['user_id' => $a->id]);
        $this->assertDatabaseHas('cart_items',['user_id' => $a->id, 'quantity' => 2]);
        $this->assertDatabaseMissing('cart_items',['user_id' => $b->id]);
    }
}
