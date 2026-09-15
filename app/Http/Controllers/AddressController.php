<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByRaw("case when type = 'primary' then 0 else 1 end")
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Account/Addresses', [
            'addresses' => $addresses->map(fn (CustomerAddress $address) => $this->present($address)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = $request->user();
        $makePrimary = ! empty($data['is_primary']) || $user->addresses()->doesntExist();

        if ($makePrimary) {
            $user->addresses()->where('type', 'primary')->update(['type' => 'shipping']);
        }

        $user->addresses()->create([
            'type' => $makePrimary ? 'primary' : 'shipping',
            'address_line1' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'telephone' => $data['phone'],
        ]);

        return redirect()->route('account.addresses.index')->with('status', 'Address saved.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwnership($address, $request);

        $data = $this->validated($request);

        if (! empty($data['is_primary'])) {
            $request->user()->addresses()->whereKeyNot($address->id)->where('type', 'primary')->update(['type' => 'shipping']);
            $address->type = 'primary';
        } else {
            $address->type = 'shipping';
        }

        $address->update([
            'address_line1' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'country' => $data['country'],
            'postal_code' => $data['postal_code'],
            'telephone' => $data['phone'],
        ]);

        return redirect()->route('account.addresses.index')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeOwnership($address, $request);

        $address->delete();

        return redirect()->route('account.addresses.index')->with('status', 'Address removed.');
    }

    protected function authorizeOwnership(CustomerAddress $address, Request $request): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
    }

    protected function present(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'address' => $address->address_line1,
            'city' => $address->city,
            'province' => $address->province,
            'country' => $address->country,
            'postalCode' => $address->postal_code,
            'phone' => $address->telephone ?: $address->mobile,
            'isPrimary' => $address->type === 'primary',
        ];
    }
}
