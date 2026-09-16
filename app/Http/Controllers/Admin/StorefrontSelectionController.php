<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminStorefront;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StorefrontSelectionController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.storefront-selection', [
            'options' => AdminStorefront::options(),
            'selected' => AdminStorefront::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'sales_channel' => ['required', Rule::in(array_keys(AdminStorefront::options()))],
            'initial_selection' => ['nullable', 'boolean'],
        ]);

        AdminStorefront::select($validated['sales_channel']);
        $request->user('admin')->forceFill(['admin_sales_channel' => $validated['sales_channel']])->saveQuietly();

        return $request->boolean('initial_selection')
            ? redirect('/admin')
            : back();
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless(
            $request->user('admin')?->account_type === 'admin'
                && $request->user('admin')?->approval_status === 'approved',
            403,
        );
    }
}
