<?php

use App\Services\Checkout;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/bootstrap.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.connections.mysql.database') !== 'shop_test') {
    exit(2);
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
echo "ready\n";
flush();
echo $app->make(Checkout::class)->pay($input['id'], 'WELCOME10', $input['ticket'])."\n";
