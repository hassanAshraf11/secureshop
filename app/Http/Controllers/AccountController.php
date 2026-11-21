<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Addresses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController
{
    public function show(Request $request)
    {
        return view('account.index', ['orders' => DB::table('orders')->where('user_id', $request->user()->id)->latest()->get()]);
    }

    public function update(Request $request)
    {
        Addresses::normalize($request);
        $data = $request->validate(['name' => 'required|string|max:80', 'email' => Addresses::rules($request->user())]);
        [$user, $token] = DB::transaction(function () use ($request, $data) {
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            $user->name = $data['name'];
            $token = null;
            if ($data['email'] !== $user->email) {
                $user->pending_email = $data['email'];
                $token = Addresses::prepare($user);
            } else {
                $user->pending_email = $user->email_token_hash = $user->email_token_expires = null;
            }
            $user->save();

            return [$user, $token];
        });
        if ($token) {
            Addresses::send($user, $token);
        }

        return back()->with('status', $token ? 'Check your new inbox to confirm the address change.' : 'Profile saved.');
    }
}
