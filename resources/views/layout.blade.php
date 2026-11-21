<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101b2a">
    <title>@yield('title', 'Electronics & desk essentials') · SecureShop</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/shop.css">
    <script defer src="/shop.js"></script>
</head>

<body>
    <a class="skip" href="#main">Skip to content</a>
    <header class="mast">
        <div class="wrap navrow">
            <a class="brand" href="/" aria-label="SecureShop home"><x-icon
                    name="logo" />SecureShop<span>.</span></a>
            <nav class="navlinks" aria-label="Store"><a href="/#catalogue" @class(['on' => request()->is('/')])>Shop</a><a
                    href="/#newsletter">Newsletter</a></nav>
            <form class="navsearch" method="get" action="/#catalogue" role="search">
                <x-icon name="search" /><input type="search" name="q" aria-label="Search products"
                    value="{{ is_string(request('q')) ? request('q') : '' }}" placeholder="Search products…"
                    maxlength="100">
            </form>
            <div class="navtools"><a href="/account"><x-icon name="user" /><span>Account</span></a>
                <a class="cartlink" href="/cart"><x-icon
                        name="bag" /><span>Cart</span><b>{{ auth()->check() ? (int) DB::table('cart_items')->where('user_id', auth()->id())->sum('quantity') : 0 }}</b></a>
            </div>
        </div>
    </header>
    <main id="main" class="wrap">
        @if (session('status'))
            <div class="notice good" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice bad" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @yield('content')
    </main>
    <footer>
        <div class="wrap foot"><a class="brand smallbrand" href="/"><x-icon
                    name="logo" />SecureShop<span>.</span></a><span>CET431 · Software Security</span><a
                href="/#catalogue">Shop electronics</a></div>
    </footer>
</body>

</html>
