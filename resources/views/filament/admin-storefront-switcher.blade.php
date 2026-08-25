<form
    method="POST"
    action="{{ route('admin.storefront.store') }}"
    class="admin-storefront-switcher"
>
    @csrf

    <div class="admin-storefront-select-wrap">
        <span class="admin-storefront-dot" aria-hidden="true"></span>
        <select
            id="admin-sales-channel"
            name="sales_channel"
            class="admin-storefront-select"
            aria-label="Website"
            required
            onchange="this.form.submit()"
        >
            @foreach (\App\Support\AdminStorefront::websiteOptions() as $value => $label)
                <option value="{{ $value }}" @selected(\App\Support\AdminStorefront::current() === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <noscript>
        <button type="submit" class="admin-storefront-submit">Change</button>
    </noscript>
</form>
