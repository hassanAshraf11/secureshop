<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function lines(int $userId, bool $lock = false)
    {
        $query = DB::table('cart_items as c')->join('products as p', 'p.id', '=', 'c.product_id')
            ->where('c.user_id', $userId)->orderBy('p.id')->select('p.*', 'c.quantity');

        return ($lock ? $query->lockForUpdate() : $query)->get();
    }

    public function quote(User $user, ?string $code, bool $lock = false): array
    {
        $lines = $this->lines($user->id, $lock);
        $subtotal = (int) $lines->sum(fn ($p) => $p->price * $p->quantity);
        $coupon = null;
        $discount = 0;
        if ($code) {
            $query = DB::table('coupons')->where('code', $code);
            $coupon = ($lock ? $query->lockForUpdate() : $query)->first();
            $used = $coupon && DB::table('coupon_redemptions')->where(['coupon_id' => $coupon->id, 'user_id' => $user->id])->exists();
            if (! $coupon || ! $coupon->active || now()->gte($coupon->expires_at) || $coupon->used >= $coupon->max_uses ||
                ($coupon->subscriber_email && $coupon->subscriber_email !== $user->email) || $used) {
                throw ValidationException::withMessages(['coupon' => 'This coupon is unavailable for this account. Remove it or try another code.']);
            }
            $discount = intdiv($subtotal * $coupon->percent, 100);
        }
        $total = $subtotal - $discount;

        return compact('lines', 'coupon', 'subtotal', 'discount', 'total');
    }

    private function signature(array $quote): string
    {
        $items = $quote['lines']->map(fn ($p) => [$p->id, $p->price, $p->quantity, $p->name])->all();

        return hash('sha256', json_encode([$items, $quote['coupon']?->code, $quote['total']], JSON_THROW_ON_ERROR));
    }

    public function ticket(User $user, array $quote): string
    {
        return Crypt::encryptString(json_encode([
            'key' => (string) Str::uuid(), 'user' => $user->id, 'signature' => $this->signature($quote),
            'expires' => now()->addMinutes(20)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    public function pay(int $userId, ?string $code, string $ticket): int
    {
        try {
            $claim = json_decode(Crypt::decryptString($ticket), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['checkout' => 'Refresh checkout to get a new order summary.']);
        }
        abort_unless($claim['user'] === $userId, 403);

        return DB::transaction(function () use ($userId, $code, $claim) {
            // Cart mutations take this same lock first. Concurrent retries wait for the first commit.
            $user = User::lockForUpdate()->findOrFail($userId);
            abort_unless($user->active && $user->email_verified_at, 403);
            $existing = DB::table('orders')->where(['user_id' => $userId, 'purchase_key' => $claim['key']])->value('id');
            if ($existing) {
                return $existing;
            }

            $quote = $this->quote($user, $code, true);
            if ($claim['expires'] < time() || $quote['lines']->isEmpty() || ! hash_equals($claim['signature'], $this->signature($quote))) {
                throw ValidationException::withMessages(['checkout' => 'Your order summary changed or expired. Review the updated cart before paying.']);
            }
            foreach ($quote['lines'] as $p) {
                if (! $p->active || $p->stock < $p->quantity) {
                    throw ValidationException::withMessages(['stock' => $p->name.' is unavailable in that quantity. Update your cart.']);
                }
            }
            if ($user->balance < $quote['total']) {
                throw ValidationException::withMessages(['balance' => 'Insufficient store credit. Reduce your quantity or remove an item.']);
            }
            $user->balance -= $quote['total'];
            $user->save();
            $order = DB::table('orders')->insertGetId([
                'user_id' => $userId, 'purchase_key' => $claim['key'], 'subtotal' => $quote['subtotal'], 'discount' => $quote['discount'],
                'total' => $quote['total'], 'balance_after' => $user->balance, 'coupon_code' => $quote['coupon']?->code, 'created_at' => now(),
            ]);
            foreach ($quote['lines'] as $p) {
                DB::table('order_items')->insert(['order_id' => $order, 'product_id' => $p->id, 'name' => $p->name, 'price' => $p->price, 'quantity' => $p->quantity]);
                DB::table('products')->where('id', $p->id)->decrement('stock', $p->quantity);
            }
            if ($coupon = $quote['coupon']) {
                DB::table('coupon_redemptions')->insert(['coupon_id' => $coupon->id, 'user_id' => $userId, 'order_id' => $order]);
                DB::table('coupons')->where('id', $coupon->id)->increment('used');
            }
            DB::table('cart_items')->where('user_id', $userId)->delete();

            return $order;
        }, attempts: 3);
    }
}
