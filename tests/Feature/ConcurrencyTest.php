<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Checkout;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class ConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_two_database_connections_replay_one_purchase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run the MySQL suite for the independent-connection lock test.');
        }
        $this->seed();
        $user = User::where('email', 'customer@secureshop.test')->firstOrFail();
        $product = DB::table('products')->where('category', 'Keyboards')->firstOrFail();
        DB::table('cart_items')->insert(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $checkout = app(Checkout::class);
        $ticket = $checkout->ticket($user, $checkout->quote($user, 'WELCOME10'));
        $workers = [];
        // Hold the checkout lock while two independent PHP processes reach the payment step.
        DB::beginTransaction();
        try {
            User::lockForUpdate()->findOrFail($user->id);
            for ($i = 0; $i < 2; $i++) {
                $worker = new Process([PHP_BINARY, base_path('tests/checkout-worker.php')], base_path(), [
                    'APP_KEY' => config('app.key'), 'APP_ENV' => 'testing', 'SHOP_TEST_MYSQL' => '1',
                ], json_encode(['id' => $user->id, 'ticket' => $ticket]));
                $worker->start();
                $workers[] = $worker;
                $this->assertTrue($worker->waitUntil(fn ($type, $output) => str_contains($output, 'ready')));
            }
        } finally {
            DB::rollBack();
        }
        foreach ($workers as $worker) {
            $this->assertSame(0, $worker->wait(), $worker->getErrorOutput());
        }
        $this->assertSame($workers[0]->getOutput(), $workers[1]->getOutput());
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('coupon_redemptions', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => $product->stock - 1]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 55000]);
    }
}
