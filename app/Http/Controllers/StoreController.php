<?php

namespace App\Http\Controllers;

use App\Services\Addresses;
use App\Services\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'category' => ['nullable', Rule::in(Catalog::CATEGORIES)],
            'sort' => ['nullable', Rule::in(['featured', 'low', 'high', 'name'])],
        ]);
        $products = DB::table('products')->where('active', true);
        if ($filters['q'] ?? null) {
            $products->where('name', 'like', '%'.$filters['q'].'%');
        }
        if ($filters['category'] ?? null) {
            $products->where('category', $filters['category']);
        }
        [$column,$direction] = match ($filters['sort'] ?? '') {
            'low' => ['price', 'asc'], 'high' => ['price', 'desc'], 'name' => ['name', 'asc'], default => ['id', 'asc'],
        };

        return view('store.index', [
            'products' => $products->orderBy($column, $direction)->orderBy('id')->get(),
            'categories' => Catalog::CATEGORIES, 'filters' => $filters,
            'featured' => DB::table('products')->where('name', 'Forma 68 Mechanical Keyboard')->where('active', true)->first(),
        ]);
    }

    public function product(int $id)
    {
        return view('store.product', ['p' => DB::table('products')->where('active', true)->where('id', $id)->firstOrFail()]);
    }

    public function newsletter(Request $request)
    {
        Addresses::normalize($request);
        $email = $request->validate(['email' => 'required|email:rfc|max:254'])['email'];
        // The unique subscriber index selects one permanent coupon even during concurrent signups.
        DB::table('coupons')->insertOrIgnore([
            'subscriber_email' => $email, 'code' => 'DESK-'.strtoupper(Str::random(14)), 'percent' => 10,
            'expires_at' => now()->addDays(30), 'max_uses' => 1,
        ]);
        $coupon = DB::table('coupons')->where('subscriber_email', $email)->firstOrFail();
        Mail::raw('Your personal SecureShop coupon is '.$coupon->code.".\nDiscount: ".$coupon->percent.
            "%.\nValid until ".$coupon->expires_at.' UTC, while eligible. Use the same verified email at checkout.'.
            "\nRepeat signups keep the original expiry and redemption.",
            fn ($m) => $m->to($email)->subject('Your SecureShop newsletter coupon'));

        return redirect('/#newsletter')->with('status', 'Check your inbox for your newsletter coupon.');
    }
}
