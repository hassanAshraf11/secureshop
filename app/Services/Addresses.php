<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Addresses
{
    public static function normalize(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
    }

    public static function rules(?User $user = null): array
    {
        return ['required', 'string', 'email:rfc', 'max:254', Rule::unique('users', 'email')->ignore($user?->id)];
    }

    public static function prepare(User $user): string
    {
        $token = Str::random(64);
        $user->email_token_hash = hash('sha256', $token);
        $user->email_token_expires = now()->addMinutes(30);
        $user->save();

        return $token;
    }

    public static function send(User $user, string $token): void
    {
        // A URL fragment stays in the browser and out of server access logs.
        $url = rtrim(config('app.url'), '/').'/verify/'.$user->id.'#'.$token;
        Mail::raw("Confirm your SecureShop email within 30 minutes:\n\n".$url.
            "\n\nOpen the link and choose Confirm email. You can also paste this verification key:\n".$token,
            fn ($message) => $message->to($user->pending_email ?? $user->email)->subject('Confirm your SecureShop email'));
    }
}
