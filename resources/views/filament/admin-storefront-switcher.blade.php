<form class="admin-storefront-switcher" method="POST" action="{{ route('admin.storefront.store') }}">
    @csrf
    <label class="sr-only" for="admin-sales-channel">Managing website</label>
    <select id="admin-sales-channel" name="sales_channel" onchange="this.form.requestSubmit()" aria-label="Managing website">
        @foreach (\App\Support\AdminStorefront::options() as $value => $label)
            <option value="{{ $value }}" @selected(\App\Support\AdminStorefront::current() === $value)>{{ $label }}</option>
        @endforeach
    </select>
</form>
