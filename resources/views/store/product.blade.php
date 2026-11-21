@extends('layout')
@section('title', $p->name)
@section('content')
    <div class="crumb"><a href="/#catalogue">Shop</a> / <a
            href="/?category={{ $p->category }}#catalogue">{{ $p->category }}</a></div>
    <div class="product-detail"><img src="/images/{{ $p->image }}" alt="{{ $p->name }}">
        <section>
            <div class="eyebrow"><i></i>{{ $p->category }}</div>
            <h1>{{ $p->name }}</h1>
            <p class="lead">{{ $p->description }}</p>
            <h2>{{ \App\Services\Catalog::money($p->price) }}</h2>
            <p class="stock">{{ $p->stock ? $p->stock . ' in stock' : 'Currently sold out' }}</p>
            @if ($p->stock)
                @auth<form method="post" action="/cart/{{ $p->id }}">@csrf<label for="qty">Quantity</label>
                        <div class="inline"><input id="qty" type="number" name="quantity" min="1"
                                max="{{ min(99, $p->stock) }}" value="1" required><button class="action hot"><x-icon
                                    name="bag" /> Add to cart</button></div>
                    </form><a class="textlink" href="/cart">View cart</a>
            @else<a class="action hot" href="/login">Sign in to shop</a>@endauth
        @endif
    </section>
</div>@endsection
