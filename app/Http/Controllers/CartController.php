<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController
{
    public function change(Request $request, int $id)
    {
        $remove = $request->isMethod('delete');
        $quantity = $remove ? 0 : (int) $request->validate(['quantity' => 'required|integer|min:1|max:99'])['quantity'];
        DB::transaction(function () use ($request, $id, $quantity, $remove) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            $key = ['user_id' => $user->id, 'product_id' => $id];
            $cart = DB::table('cart_items')->where($key);
            if ($remove) {
                $cart->delete();

                return;
            }
            $p = DB::table('products')->where('id', $id)->lockForUpdate()->firstOrFail();
            $existing = $cart->value('quantity');
            abort_if($request->isMethod('patch') && $existing === null, 404);
            $desired = $request->isMethod('post') ? $quantity + (int) $existing : $quantity;
            if (! $p->active || $desired > min(99, $p->stock)) {
                throw ValidationException::withMessages(['quantity' => 'That quantity is unavailable. Choose fewer items or remove the product.']);
            }
            DB::table('cart_items')->updateOrInsert($key, ['quantity' => $desired]);
        });

        return back()->with('status', $remove ? 'Item removed from cart.' : 'Cart updated.');
    }

    public function show(Request $request, Checkout $checkout)
    {
        $lines = $checkout->lines($request->user()->id);

        return view('store.cart', ['lines' => $lines, 'subtotal' => $lines->sum(fn ($p) => $p->price * $p->quantity)]);
    }

    public function review(Request $request, Checkout $checkout)
    {
        $code = $request->session()->get('coupon');
        $warning = null;
        try {
            $quote = $checkout->quote($request->user(), $code);
        } catch (ValidationException $e) {
            $warning = $e->errors()['coupon'][0];
            $quote = $checkout->quote($request->user(), null);
        }
        if ($quote['lines']->isEmpty()) {
            return redirect('/cart');
        }

        return view('store.checkout', $quote + [
            'selected' => $code, 'warning' => $warning, 'ticket' => $warning ? null : $checkout->ticket($request->user(), $quote),
        ]);
    }

    public function coupon(Request $request, Checkout $checkout)
    {
        $code = strtoupper(trim($request->validate(['coupon' => 'nullable|string|max:80'])['coupon'] ?? ''));
        $checkout->quote($request->user(), $code);
        $request->session()->put('coupon', $code ?: null);

        return redirect('/checkout')->with('status', $code ? 'Coupon applied.' : 'Coupon removed.');
    }

    public function pay(Request $request, Checkout $checkout)
    {
        $ticket = $request->validate(['ticket' => 'required|string|max:4096'])['ticket'];
        $order = $checkout->pay($request->user()->id, $request->session()->get('coupon'), $ticket);
        $request->session()->forget('coupon');

        return redirect('/orders/'.$order, 303);
    }

    public function receipt(Request $request, int $id)
    {
        $order = DB::table('orders')->where('user_id', $request->user()->id)->where('id', $id)->firstOrFail();

        return view('store.receipt', ['order' => $order, 'items' => DB::table('order_items')->where('order_id', $id)->get()]);
    }
}
