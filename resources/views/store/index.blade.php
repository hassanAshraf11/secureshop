@extends('layout')
@section('content')
    @if (empty($filters['q']) && empty($filters['category']))
        <section class="hero">
            <div class="hero-copy">
                <div class="eyebrow light"><i></i> The desk collection</div>
                <h1>Upgrade your<br><em>workspace.</em></h1>
                <p>Keyboards, audio and everyday electronics<br class="wide"> for a desk that works for you.</p>
                <a href="#catalogue" class="action hot">Shop collection</a>
            </div>
            @if ($featured)
                <a class="hero-caption" href="/products/{{ $featured->id }}"><i></i> Featured <b>Forma 68</b></a>
            @endif
        </section>
    @endif
    <section id="catalogue" class="catalogue">
        <div class="sectionhead">
            <div>
                <div class="eyebrow"><i></i> Product catalogue</div>
                @if (!empty($filters['q']))
                    <h1>Search results</h1>
                @elseif (!empty($filters['category']))
                    <h1>{{ $filters['category'] }}</h1>
                @else
                    <h2>Explore the shop</h2>
                @endif
            </div>
            <form method="get" action="/#catalogue" class="sort"><x-icon name="sort" />
                @foreach (['q', 'category'] as $key)
                    @if (!empty($filters[$key]))
                        <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
                    @endif
                @endforeach
                <label for="sort">Sort by</label><select id="sort" name="sort" data-auto-submit>
                    @foreach (['featured' => 'Featured', 'low' => 'Price: low to high', 'high' => 'Price: high to low', 'name' => 'Name A–Z'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'featured') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button>Sort</button></noscript>
            </form>
        </div>
        <div class="chips" aria-label="Product categories">
            @foreach (array_merge(['All products'], $categories) as $category)
                @php($value = $loop->first ? null : $category)
                <a @class([
                    'chip',
                    'selected' => ($filters['category'] ?? null) === $value,
                ])
                    href="{{ route('store', array_filter(array_merge($filters, ['category' => $value]))) }}#catalogue"><x-icon
                        :name="$loop->first ? 'grid' : strtolower($category)" />{{ $category }}</a>
            @endforeach
        </div>
        @if (!empty($filters['q']))
            <p>{{ $products->count() }} results for “{{ $filters['q'] }}” · <a href="/#catalogue">Clear search</a></p>
        @endif
        <div class="tiles">
            @forelse($products as $p)
                <article class="tile">
                    <a class="productimage" href="/products/{{ $p->id }}"><img src="/images/{{ $p->image }}"
                            alt="{{ $p->name }}" width="900" height="675"
                            @if ($loop->index > 2) loading="lazy" @endif></a>
                    <div class="tilebody">
                        <div class="tilemeta"><span>{{ $p->category }}</span><small
                                @class(['stock', 'out' => !$p->stock])><i></i>{{ $p->stock ? 'In stock' : 'Sold out' }}</small></div>
                        <h3><a href="/products/{{ $p->id }}">{{ $p->name }}</a></h3>
                        <div class="pricebar"><strong>{{ \App\Services\Catalog::money($p->price) }}</strong><a
                                class="detailbtn" href="/products/{{ $p->id }}"
                                aria-label="View {{ $p->name }}">View product</a></div>
                    </div>
                </article>
            @empty<div class="empty"><x-icon name="search" />
                    <h3>No matching products</h3>
                    <p>Try another name or browse all categories.</p><a class="action dark" href="/#catalogue">Show all
                        products</a>
                </div>
            @endforelse
        </div>
    </section>
    <section id="newsletter" class="newsletter">
        <div>
            <div class="eyebrow"><i></i> Newsletter</div>
            <h2>Take 10% off your setup.</h2>
            <p>Subscribe for a personal coupon, delivered by email.</p>
        </div>
        <form method="post" action="/newsletter">@csrf<label for="newsletter-email">Email address</label>
            <div class="inline"><input id="newsletter-email" type="email" name="email" placeholder="you@example.com"
                    required><button class="action dark">Subscribe</button></div>
        </form>
    </section>
@endsection
