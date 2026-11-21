@extends('layout')
@section('title', 'Manage shop')
@section('content')<div class="pagetitle">
        <div class="eyebrow"><i></i> Administration</div>
        <h1>Manage shop</h1>
    </div>
    <div class="chips adminchips">
        @foreach (['products', 'users', 'coupons'] as $tab)
            <a @class(['chip', 'selected' => $tab === $section]) href="/admin/{{ $tab }}">{{ ucfirst($tab) }}</a>
        @endforeach
    </div>
    @if ($section !== 'users')
        <details class="panel edit">
            <summary>Add {{ $section === 'products' ? 'product' : 'coupon' }}</summary>@include('admin.form', ['record' => null])
        </details>
    @endif
    @foreach ($rows as $row)
        <details class="panel edit">
            <summary>{{ $row->name ?? $row->code }} <small>{{ $row->active ? 'Active' : 'Inactive' }}</small></summary>
            @if ($section === 'users')
                <p>{{ $row->email }} · {{ ucfirst($row->role) }} · {{ \App\Services\Catalog::money($row->balance) }}</p>
                <form action="/admin/users/{{ $row->id }}" method="post">@csrf @method('PATCH')<input type="hidden"
                        name="active" value="{{ $row->active ? 0 : 1 }}"><button
                        class="action dark">{{ $row->active ? 'Deactivate' : 'Activate' }} account</button></form>
            @else
                @include('admin.form', ['record' => $row])
            @endif
        </details>
    @endforeach @endsection
