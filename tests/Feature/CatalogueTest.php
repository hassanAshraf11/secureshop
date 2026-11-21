<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    public function test_catalogue_search_category_sort_and_detail(): void
    {
        $p = $this->keyboard();
        $this->get('/')->assertOk()->assertSee($p->name);
        $this->get('/?q=Forma&category=Keyboards&sort=high')->assertSee($p->name)->assertDontSee('Arc Wireless Mouse');
        $this->get('/?q=missingproduct')->assertSee('No matching products');
        $this->get('/?sort=unknown')->assertSessionHasErrors('sort');
        $this->get('/products/'.$p->id)->assertOk()->assertSee('500 EGP');
        DB::table('products')->where('id', $p->id)->update(['active' => false]);
        $this->get('/products/'.$p->id)->assertNotFound();
    }

    public function test_newsletter_resends_one_coupon_and_checks_recipient_and_cap(): void
    {
        $email = $this->user()->email;
        $this->post('/newsletter', ['email' => strtoupper($email)])->assertRedirect();
        $q = DB::table('coupons')->where('subscriber_email', $email);
        $original = $q->firstOrFail();
        $this->post('/newsletter', ['email' => $email]);
        $this->assertEquals($original, $q->first());
        $this->assertSame(1, $q->count());
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(2, $messages);
        $this->assertStringContainsString($original->code, $messages->last()->getOriginalMessage()->getTextBody());
        $this->signIn('second');
        $this->post('/checkout/coupon', ['coupon' => $original->code])->assertSessionHasErrors('coupon');
        $this->signIn();
        $this->post('/checkout/coupon', ['coupon' => $original->code])->assertSessionHasNoErrors();
        $q->update(['used' => 1]);
        $this->post('/newsletter', ['email' => $email]);
        $this->post('/checkout/coupon', ['coupon' => $original->code])->assertSessionHasErrors('coupon');
        $this->assertSame(1, (int) DB::table('coupons')->where('id', $original->id)->value('used'));
    }
}
