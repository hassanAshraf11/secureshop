@extends('layout')
@section('title', 'Your cart')
@section('content')<div class="pagetitle">
        <div class="eyebrow"><i></i> Shopping cart</div>
        <h1>Your cart</h1>
    </div>
    @if ($lines->isEmpty())
        <div class="empty"><x-icon name="bag" />
            <h2>Your cart is empty</h2>
            <p>Browse the collection to find your next desk essential.</p><a href="/#catalogue" class="action hot">Explore the
                shop</a>
        </div>
    @else<div class="split">
            <div class="panel">
                @foreach ($lines as $p)
                    <article class="cartrow"><img src="/images/{{ $p->image }}" alt="">
                        <div>
                            <div class="eyebrow">{{ $p->category }}</div>
                            <h3>{{ $p->name }}</h3>
                            <p>{{ \App\Services\Catalog::money($p->price) }} each</p>
                            @if (!$p->active || $p->stock < $p->quantity)
                                <p class="bad">Unavailable in this quantity. Adjust or remove this item.</p>
                            @endif
                            <div class="lineactions">
                                <form method="post" action="/cart/{{ $p->id }}">@csrf @method('PATCH')<label>Qty
                                        <input type="number" name="quantity" min="1" max="99"
                                            value="{{ $p->quantity }}" required></label><button
                                        class="linkbtn">Update</button></form>
                                <form method="post" action="/cart/{{ $p->id }}">@csrf @method('DELETE')<button
                                        class="linkbtn muted">Remove</button></form>
                            </div>
                        </div>
                        <strong>{{ \App\Services\Catalog::money($p->price * $p->quantity) }}</strong>
                    </article>
                @endforeach
            </div>
            <aside class="panel summary">
                <h2>Order summary</h2>
                <div class="sum"><span>Subtotal</span><b>{{ \App\Services\Catalog::money($subtotal) }}</b></div>
                <p>Review your coupon and store credit at checkout.</p><a class="action hot" href="/checkout">Review
                    checkout</a><a class="textlink" href="/#catalogue">Continue shopping</a>
            </aside>
        </div>
    @endif
@endsection
