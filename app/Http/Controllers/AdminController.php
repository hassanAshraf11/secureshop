<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController
{
    public function index(string $section = 'products')
    {
        $rows = match ($section) {
            'users' => User::orderBy('id')->get(),
            'products','coupons' => DB::table($section)->orderBy('id')->get(),
            default => abort(404),
        };

        return view('admin.index', compact('section', 'rows'));
    }

    public function user(Request $request, int $id)
    {
        $active = $request->validate(['active' => 'required|boolean'])['active'];
        if ($id === $request->user()->id && ! $active) {
            throw ValidationException::withMessages(['user' => 'Keep your own administrator account active.']);
        }
        User::findOrFail($id)->forceFill(['active' => (bool) $active])->save();

        return back()->with('status', 'User status saved.');
    }

    public function product(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('products')->ignore($id)],
            'category' => ['required', Rule::in(Catalog::CATEGORIES)], 'description' => 'required|string|max:600',
            'egp' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'], 'stock' => 'required|integer|min:0|max:100000',
            'active' => 'required|boolean',
        ]);
        [$whole,$fraction] = array_pad(explode('.', (string) $data['egp']), 2, '');
        $data['price'] = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        unset($data['egp']);
        $data['image'] = Catalog::IMAGES[$data['category']];
        if ($id) {
            DB::table('products')->where('id', $id)->firstOrFail();
            DB::table('products')->where('id', $id)->update($data);
        } else {
            DB::table('products')->insert($data);
        }

        return back()->with('status', 'Product saved.');
    }

    public function coupon(Request $request, ?int $id = null)
    {
        $data = $request->validate([
            'percent' => 'required|integer|between:1,100', 'expires_at' => 'required|date_format:Y-m-d\TH:i',
            'max_uses' => 'required|integer|between:1,1000000', 'active' => 'required|boolean',
        ]);
        $data['expires_at'] = str_replace('T', ' ', $data['expires_at']).':00';
        if ($id) {
            DB::table('coupons')->where('id', $id)->firstOrFail();
            DB::table('coupons')->where('id', $id)->update($data);
        } else {
            $request->merge(['code' => strtoupper((string) $request->string('code'))]);
            $data += $request->validate(['code' => 'required|regex:/^[A-Z0-9-]{3,40}$/|unique:coupons,code']);
            DB::table('coupons')->insert($data);
        }

        return back()->with('status','Coupon saved.');
    }
}
