<form method="POST" action="{{ route('admin.storefront.store') }}" style="display:flex;align-items:center;gap:.5rem;margin-inline:auto .75rem;">
    @csrf
    <label for="admin-sales-channel" style="font-size:.75rem;font-weight:700;color:rgb(113 113 122);white-space:nowrap;">Managing</label>
    <select id="admin-sales-channel" name="sales_channel" onchange="this.form.submit()" style="min-width:13rem;border:1px solid rgb(212 212 216);border-radius:.5rem;background:transparent;padding:.5rem .75rem;font-size:.875rem;font-weight:600;">
        @foreach (\App\Support\AdminStorefront::options() as $value => $label)
            <option value="{{ $value }}" @selected(\App\Support\AdminStorefront::current() === $value)>{{ $label }}</option>
        @endforeach
    </select>
</form>
