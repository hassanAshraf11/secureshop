<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Addresses;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController
{
    public function register(Request $request)
    {
        Addresses::normalize($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'], 'email' => Addresses::rules(),
            'password' => ['bail', 'required', 'string', 'min:12', 'max:72', 'confirmed',
                function ($attribute, $value, $fail) {
                    if (strlen($value) > 72) {
                        $fail('Use a password of up to 72 bytes.');
                    }
                }],
        ]);
        $user = User::create($data);
        Addresses::send($user, Addresses::prepare($user));

        return redirect('/login')->with('status', 'Check your email to confirm your address. Then sign in and we will email you a sign-in code.');
    }

    public function verify(Request $request, int $id)
    {
        $token = $request->validate(['token' => 'required|string|size:64'])['token'];
        try {
            DB::transaction(function () use ($id, $token) {
                $user = User::lockForUpdate()->findOrFail($id);
                if (! $user->active || ! $user->email_token_expires?->isFuture() ||
                    ! hash_equals($user->email_token_hash ?? '', hash('sha256', $token))) {
                    throw ValidationException::withMessages(['email' => 'This link is invalid or expired. Request a fresh email.']);
                }
                $user->email = $user->pending_email ?? $user->email;
                $user->pending_email = null;
                $user->email_verified_at = now();
                $user->email_token_hash = $user->email_token_expires = null;
                $user->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'That address is already registered. Choose another address.']);
        }

        return redirect(Auth::check() ? '/account' : '/login')->with('status', 'Email confirmed. A one-time sign-in code will be sent to your inbox.');
    }

    public function login(Request $request)
    {
        $request->session()->forget('challenge');
        Addresses::normalize($request);
        $data = $request->validate(['email' => 'required|email|max:254', 'password' => 'required|string|max:256']);
        $user = User::where('email', $data['email'])->first();
        $correct = Hash::check($data['password'], $user?->password ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $user?->active || ! $correct) {
            throw ValidationException::withMessages(['email' => 'The sign-in details could not be verified.']);
        }
        if (! $user->email_verified_at) {
            Addresses::send($user, Addresses::prepare($user));

            return back()->with('status', 'We sent a fresh confirmation link. Confirm your email before signing in.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $nonce = Str::random(64);
        DB::transaction(function () use ($user, $code, $nonce) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            if ($locked->login_otp_locked_until && now()->lt($locked->login_otp_locked_until)) {
                throw ValidationException::withMessages(['email' => 'Sign-in codes are temporarily locked. Please wait five minutes before trying again.']);
            }
            $locked->login_otp_hash = hash_hmac('sha256', $code, config('app.key'));
            $locked->login_otp_challenge_hash = hash('sha256', $nonce);
            $locked->login_otp_expires = now()->addSeconds(60);
            $locked->save();
        });

        Mail::raw("Your SecureShop sign-in code is: ".$code."\n\nIt expires in 60 seconds. Enter it only on the SecureShop sign-in page. Do not share this code.",
            fn ($message) => $message->to($user->email)->subject('Your SecureShop sign-in code'));

        $request->session()->regenerate(true);
        $request->session()->put('challenge', [
            'user' => $user->id,
            'nonce' => $nonce,
            'expires' => time() + 60,
        ]);

        return redirect('/email-code')->with('status', 'We emailed a six-digit sign-in code. It expires in 60 seconds.');
    }

    private function challenge(Request $request): array
    {
        $challenge = $request->session()->get('challenge');
        if (! $challenge || ($challenge['expires'] ?? 0) < time() || empty($challenge['user']) || empty($challenge['nonce'])) {
            $request->session()->forget('challenge');
            throw new HttpResponseException(
                redirect('/login')->withErrors(['login' => 'Your sign-in code expired. Enter your password again to request a new one.'])
            );
        }

        return $challenge;
    }

    public function emailCodeForm(Request $request)
    {
        $this->challenge($request);

        return view('auth.email-code');
    }

    public function verifyEmailCode(Request $request)
    {
        $challenge = $this->challenge($request);
        $code = $request->validate(['code' => 'required|digits:6'])['code'];
        $result = DB::transaction(function () use ($challenge, $code) {
            $user = User::lockForUpdate()->findOrFail($challenge['user']);
            if (! $user->active || ! $user->email_verified_at) {
                return 'invalid';
            }
            if ($user->login_otp_locked_until && now()->lt($user->login_otp_locked_until)) {
                return 'locked';
            }
            if (! $user->login_otp_hash || ! $user->login_otp_challenge_hash ||
                ! $user->login_otp_expires?->isFuture() ||
                ! hash_equals($user->login_otp_challenge_hash, hash('sha256', $challenge['nonce']))) {
                return 'expired';
            }

            $submitted = hash_hmac('sha256', $code, config('app.key'));
            if (! hash_equals($user->login_otp_hash, $submitted)) {
                $user->login_otp_failures++;
                if ($user->login_otp_failures >= 5) {
                    $user->login_otp_locked_until = now()->addMinutes(5);
                    $user->login_otp_failures = 0;
                }
                $user->save();

                return $user->login_otp_locked_until ? 'locked' : 'wrong';
            }

            $user->login_otp_hash = null;
            $user->login_otp_challenge_hash = null;
            $user->login_otp_expires = null;
            $user->login_otp_failures = 0;
            $user->login_otp_locked_until = null;
            $user->save();

            return 'ok';
        });

        if ($result !== 'ok') {
            if (in_array($result, ['expired', 'invalid'], true)) {
                $request->session()->forget('challenge');

                return redirect('/login')->withErrors(['login' => 'Your sign-in code expired. Enter your password again to request a new one.']);
            }
            $message = $result === 'locked'
                ? 'Too many incorrect codes. Wait five minutes before requesting another code.'
                : 'That code is incorrect. Check the latest email and enter its six-digit code.';
            throw ValidationException::withMessages(['code' => $message]);
        }

        $request->session()->forget('challenge');
        $user = User::findOrFail($challenge['user']);
        Auth::login($user);
        $request->session()->regenerate(true);
        $request->session()->put('mfa_user', $user->id);

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'You have signed out.');
    }
}
