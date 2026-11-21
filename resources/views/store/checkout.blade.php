@extends('layout')
@section('title', 'Checkout')
@section('content')<div class="crumb">
        <a href="/cart">Cart</a> / Checkout
    </div>
    <div class="pagetitle">
        <div class="eyebrow"><i></i> Store credit payment</div>
        <h1>Review your order</h1>
    </div>
    <div class="split">
        <div>
            <section class="panel">
                <h2>Items</h2>
                @foreach ($lines as $p)
                    <div class="sum"><span>{{ $p->quantity }} ×
                            {{ $p->name }}<small>{{ \App\Services\Catalog::money($p->price) }}
                                each</small></span><b>{{ \App\Services\Catalog::money($p->price * $p->quantity) }}</b></div>
                @endforeach
            </section>
            <section class="panel">
                <h2>Discount coupon</h2>
                <form method="post" action="/checkout/coupon">@csrf<label for="coupon">Coupon code</label>
                    <div class="inline"><input id="coupon" name="coupon" value="{{ $selected }}"
                            placeholder="Enter code" maxlength="80"><button class="action dark">Apply</button></div>
                </form>
                @if ($selected)
                    <form method="post" action="/checkout/coupon">@csrf<button class="linkbtn">Remove
                            {{ $selected }}</button></form>
                    @endif @if ($warning)
                        <p class="notice bad">{{ $warning }}</p>
                    @endif
            </section>
        </div>
        <section class="panel summary">
            <h2>Payment summary</h2>
            <div class="sum"><span>Subtotal</span><b>{{ \App\Services\Catalog::money($subtotal) }}</b></div>
            <div class="sum"><span>Discount
                    {{ $coupon ? '(' . $coupon->percent . '%)' : '' }}</span><b>−{{ \App\Services\Catalog::money($discount) }}</b>
            </div>
            <div class="sum grand"><span>Total</span><b>{{ \App\Services\Catalog::money($total) }}</b></div>
            <div class="sum"><span>Available
                    credit</span><b>{{ \App\Services\Catalog::money(auth()->user()->balance) }}</b></div>
            @php($unavailable = $lines->contains(fn($p) => !$p->active || $p->quantity > $p->stock))
            @if ($total > auth()->user()->balance)
                <p class="notice bad">Insufficient credit. Remove an item or choose a smaller quantity.</p>
            @endif
            @if ($unavailable)
                <p class="notice bad">An item is unavailable in the selected quantity. Update your cart.</p>
            @endif
            <form method="post" action="/checkout/pay">@csrf<input type="hidden" name="ticket"
                    value="{{ $ticket }}"><button class="action hot" @disabled(!$ticket || $unavailable || $total > auth()->user()->balance)>Pay
                    {{ \App\Services\Catalog::money($total) }}</button></form><a class="textlink" href="/cart">Edit
                cart</a>
        </section>
</div>@endsection
