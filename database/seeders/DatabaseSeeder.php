<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Catalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo accounts are available only in local and testing environments.');
        }
        DB::transaction(function () {
            if (DB::table('seed_runs')->insertOrIgnore(['name' => 'initial-demo']) === 1) {
                $this->createDemoData();
            }
        });
    }

    private function createDemoData(): void
    {
        foreach (['customer' => 'Mira Hassan', 'second' => 'Omar Ali', 'admin' => 'Shop Administrator'] as $alias => $name) {
            $user = User::firstOrNew(['email' => $alias.'@secureshop.test']);
            if (! $user->exists) {
                $user->name = $name;
                $user->password = 'SecureShop-Demo-431';
                $user->role = $alias === 'admin' ? 'admin' : 'customer';
                $user->email_verified_at = now();
                $user->save();
            }
        }
        $products = [
            ['Forma 68 Mechanical Keyboard', 'Keyboards', 50000, 12, 'A compact mechanical keyboard with tactile switches, two-tone keycaps and a detachable USB-C cable.'],
            ['Arc Wireless Mouse', 'Mice', 32500, 18, 'A contoured wireless mouse with quiet clicks and a precise optical sensor for everyday work.'],
            ['Studio One Headphones', 'Headphones', 75000, 8, 'Soft over-ear cushions, balanced stereo sound and an adjustable headband for long listening sessions.'],
            ['Link USB-C Hub', 'Accessories', 28000, 15, 'A slim aluminium hub that brings extra USB ports and a display connection to your desk.'],
            ['Pulse Portable Speaker', 'Speakers', 42500, 5, 'A portable wireless speaker with a textured grille and carry loop for listening on the move.'],
            ['Frame 24 Desktop Monitor', 'Monitors', 250000, 4, 'A 24-inch full HD display with slim bezels and a tilt-adjustable stand.'],
        ];
        foreach ($products as [$name,$category,$price,$stock,$description]) {
            DB::table('products')->insertOrIgnore(compact('name', 'category', 'price', 'stock', 'description') + ['image' => Catalog::IMAGES[$category]]);
        }
        DB::table('coupons')->insertOrIgnore([
            'code' => 'WELCOME10', 'percent' => 10, 'expires_at' => now()->addYear(), 'max_uses' => 100,
        ]);
    }
}
