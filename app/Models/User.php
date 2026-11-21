<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'totp_secret', 'email_token_hash', 'login_otp_hash', 'login_otp_challenge_hash'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', 'totp_secret' => 'encrypted', 'active' => 'boolean',
            'email_verified_at' => 'datetime', 'email_token_expires' => 'datetime',
            'login_otp_expires' => 'datetime', 'login_otp_locked_until' => 'datetime',
        ];
    }
}
