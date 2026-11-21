<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seed_runs', function (Blueprint $t) {
            $t->string('name')->primary();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('pending_email')->nullable();
            $t->string('email_token_hash', 64)->nullable();
            $t->timestamp('email_token_expires')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->text('totp_secret')->nullable();
            $t->unsignedBigInteger('last_totp_step')->default(0);
            $t->unsignedTinyInteger('factor_failures')->default(0);
            $t->timestamp('factor_locked_until')->nullable();
            $t->string('role')->default('customer');
            $t->boolean('active')->default(true);
            $t->unsignedBigInteger('balance')->default(100000);
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('category')->index();
            $t->string('description', 600);
            $t->string('image');
            $t->unsignedInteger('price');
            $t->unsignedInteger('stock');
            $t->boolean('active')->default(true);
        });
        Schema::create('cart_items', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->primary(['user_id', 'product_id']);
        });
        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('subscriber_email')->nullable()->unique();
            $t->unsignedTinyInteger('percent');
            $t->timestamp('expires_at');
            $t->unsignedInteger('max_uses');
            $t->unsignedInteger('used')->default(0);
            $t->boolean('active')->default(true);
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->uuid('purchase_key');
            $t->unique(['user_id', 'purchase_key']);
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount');
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('balance_after');
            $t->string('coupon_code')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->string('name');
            $t->unsignedInteger('price');
            $t->unsignedInteger('quantity');
        });
        Schema::create('coupon_redemptions', function (Blueprint $t) {
            $t->foreignId('coupon_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('order_id')->unique()->constrained();
            $t->primary(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['coupon_redemptions', 'order_items', 'orders', 'coupons', 'cart_items', 'products', 'users', 'seed_runs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
