@extends('layout')
@section('title', 'Your account')
@section('content')<div class="pagetitle">
        <div class="eyebrow"><i></i> Customer account</div>
        <h1>Hello, {{ auth()->user()->name }}.</h1>
    </div>
    <div class="split">
        <section id="orders" class="panel">
            <h2>Order history</h2>
            @forelse($orders as $order)
                <div class="sum"><span><a href="/orders/{{ $order->id }}">Order #SS-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</a><small>{{ $order->created_at }}
                        UTC</small></span><b>{{ \App\Services\Catalog::money($order->total) }}</b></div>@empty<p>Your
                    receipts will appear here after your first purchase.</p><a class="action dark" href="/#catalogue">Explore
                    the shop</a>
            @endforelse
        </section>
        <aside class="panel summary">
            <div class="eyebrow"><i></i> Demo store credit</div>
            <h2>{{ \App\Services\Catalog::money(auth()->user()->balance) }}</h2>
            @if (auth()->user()->role === 'admin')
                <a class="textlink" href="/admin">Manage shop</a>
            @endif
            <form action="/logout" method="post">@csrf<button class="linkbtn">Sign out</button></form>
        </aside>
    </div>
    <section class="panel profile">
        <h2>Profile</h2>
        <p>Verified email · {{ auth()->user()->email }}</p>
        @if (auth()->user()->pending_email)
            <p class="notice">Awaiting confirmation for {{ auth()->user()->pending_email }}. Save this address again for a
                fresh link.</p>
        @endif
        <form action="/account" method="post" class="stack">@csrf @method('PATCH')<label>Full name<input name="name"
                    required value="{{ old('name', auth()->user()->name) }}"></label>
            <label>Email address<input type="email" name="email" required
                    value="{{ old('email', auth()->user()->pending_email ?? auth()->user()->email) }}"></label><button
                class="action dark">Save profile</button>
        </form>
</section>@endsection
