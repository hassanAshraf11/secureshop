@extends('layout')
@section('title', 'Order receipt')
@section('content')<section class="receipt panel">
        <div class="eyebrow"><i></i> Payment complete</div>
        <h1>Order confirmed.</h1>
        <p>Receipt <b>#SS-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</b> · {{ $order->created_at }} UTC</p>
        @foreach ($items as $p)
            <div class="sum"><span>{{ $p->name }}<small>{{ $p->quantity }} ×
                        {{ \App\Services\Catalog::money($p->price) }}</small></span><b>{{ \App\Services\Catalog::money($p->quantity * $p->price) }}</b>
            </div>
        @endforeach
        @foreach (['Subtotal' => $order->subtotal, 'Discount' . ($order->coupon_code ? ' · ' . $order->coupon_code : '') => $order->discount, 'Total paid' => $order->total, 'Remaining credit after order' => $order->balance_after] as $label => $value)
            <div @class(['sum', 'grand' => $label === 'Total paid'])>
                <span>{{ $label }}</span><b>{{ \App\Services\Catalog::money($value) }}</b></div>
        @endforeach
        <a class="action dark" href="/account#orders">Order history</a>
</section>@endsection
