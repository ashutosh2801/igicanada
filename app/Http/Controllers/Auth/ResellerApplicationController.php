<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PriceTier;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ResellerApplicationController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Auth/Apply', [
            'signedInAccount' => $user ? [
                'name' => $user->name,
                'accountType' => $user->account_type,
                'destinationLabel' => $user->account_type === 'admin' ? 'Open admin panel' : 'Open account',
                'destinationUrl' => $user->account_type === 'admin'
                    ? '/admin'
                    : ($user->isApprovedWholesale() ? route('account.dashboard', [], false) : route('account.status', [], false)),
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company' => ['required', 'string', 'max:250'],
            'phone' => ['required', 'string', 'max:30'],
            'business_number' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:300'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'account_type' => 'wholesale',
                'approval_status' => 'pending',
                'price_tier_id' => PriceTier::query()->where('is_active', true)->orderBy('discount_percentage')->value('id'),
                'must_reset_password' => false,
            ]);

            $user->resellerProfile()->create([
                'company' => $data['company'],
                'phone' => $data['phone'],
                'business_number' => $data['business_number'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
            ]);

            $user->addresses()->create([
                'type' => 'primary',
                'address_line1' => $data['address'],
                'city' => $data['city'],
                'province' => $data['province'],
                'country' => $data['country'],
                'postal_code' => $data['postal_code'],
                'telephone' => $data['phone'],
            ]);

            return $user;
        });

        $user->sendEmailVerificationNotification();

        return redirect()->route('wholesale.confirmation')->with([
            'status' => 'Application received. Please check your email and verify your address.',
            'verification_email' => $user->email,
        ]);
    }

    public function confirmation(Request $request): Response
    {
        return Inertia::render('Auth/Confirmation', [
            'email' => $request->session()->get('verification_email'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->where('account_type', 'wholesale')->findOrFail($id);

        abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('wholesale.confirmation')->with([
            'status' => 'Email verified successfully. Your wholesale application is now waiting for administrator approval.',
            'verification_email' => $user->email,
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()
            ->where('email', strtolower($data['email']))
            ->where('account_type', 'wholesale')
            ->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'If that wholesale account is awaiting verification, a new email has been sent.');
    }
}
