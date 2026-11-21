<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->char('login_otp_hash', 64)->nullable();
            $table->char('login_otp_challenge_hash', 64)->nullable();
            $table->timestamp('login_otp_expires')->nullable();
            $table->unsignedTinyInteger('login_otp_failures')->default(0);
            $table->timestamp('login_otp_locked_until')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'login_otp_hash',
                'login_otp_challenge_hash',
                'login_otp_expires',
                'login_otp_failures',
                'login_otp_locked_until',
            ]);
        });
    }
};
