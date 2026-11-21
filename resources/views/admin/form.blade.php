<form class="stack adminform" method="post" action="/admin/{{ $section }}{{ $record ? '/' . $record->id : '' }}">
    @csrf @if ($record)
        @method('PATCH')
    @endif
    @if ($section === 'products')
        <label>Name<input name="name" maxlength="100" value="{{ $record->name ?? '' }}" required></label>
        <label>Category<select name="category">
                @foreach (\App\Services\Catalog::CATEGORIES as $category)
                    <option @selected(($record->category ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select></label>
        <label>Description
            <textarea name="description" maxlength="600" required>{{ $record->description ?? '' }}</textarea>
        </label>
        <label>Price in EGP<input type="number" min="0" step=".01" name="egp" required
                value="{{ $record ? number_format($record->price / 100, 2, '.', '') : '' }}"></label>
        <label>Stock<input type="number" name="stock" min="0" value="{{ $record->stock ?? 0 }}"
                required></label>
    @else
        @if (!$record)
        <label>Coupon code<input name="code" maxlength="40" placeholder="DESK10" required></label>@else<p>
                {{ $record->used }} redemption(s) · {{ $record->subscriber_email ?? 'All verified customers' }}</p>
        @endif
        <label>Discount (%)<input type="number" name="percent" min="1" max="100"
                value="{{ $record->percent ?? 10 }}" required></label>
        <label>Expiry (UTC)<input type="datetime-local" name="expires_at"
                value="{{ \Illuminate\Support\Carbon::parse($record->expires_at ?? now()->addMonth())->format('Y-m-d\TH:i') }}"
                required></label>
        <label>Total redemption limit<input type="number" name="max_uses" min="1"
                value="{{ $record->max_uses ?? 1 }}" required></label>
    @endif
    <label>Status<select name="active">
            <option value="1" @selected(!$record || $record->active)>Active</option>
            <option value="0" @selected($record && !$record->active)>Inactive</option>
        </select></label>
    <button class="action dark">Save {{ $section === 'products' ? 'product' : 'coupon' }}</button>
</form>
