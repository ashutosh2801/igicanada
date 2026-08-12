<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAccountController extends Controller
{
    public function register(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'account_type' => 'retail',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'must_reset_password' => false,
        ]);

        $user->markEmailAsVerified();

        Auth::login($user, true);
        $request->session()->regenerate();

        $this->claimSessionOrders($request, $user);

        return redirect()->route('retail.account.dashboard');
    }

    public function login(): Response
    {
        return Inertia::render('Auth/Login', ['channel' => 'retail']);
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => strtolower($credentials['email']), 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        if ($user->account_type !== 'retail') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account is not a retail customer account. Please sign in from the wholesale website.',
            ]);
        }

        $this->claimSessionOrders($request, $user);

        return redirect()->intended(route('retail.account.dashboard'));
    }

    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $orders = $user->orders()
            ->where('sales_channel', 'retail')
            ->latest('placed_at')
            ->latest('id')
            ->limit(20)
            ->get();

        return Inertia::render('Account/Dashboard', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'createdAt' => optional($user->created_at)->format('M j, Y'),
            ],
            'orderCount' => $orders->count(),
            'recentOrders' => $orders->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'total' => number_format((float) $order->total, 2, '.', ''),
                'placedAt' => optional($order->placed_at)->format('M j, Y'),
                'items' => $order->items()->count(),
            ]),
        ]);
    }

    public function profile(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Account/Profile', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'phone' => $user->phone,
                'avatar' => $user->avatarUrl(),
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email,'.$user->id],
            'gender' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'gender' => $data['gender'] ?: null,
            'phone' => $data['phone'] ?: null,
        ]);

        if ($request->hasFile('avatar')) {
            $old = $user->avatar;
            $user->update([
                'avatar' => $request->file('avatar')->store('avatars', 'public'),
            ]);

            if ($old && str_starts_with($old, 'avatars/')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
        }

        return redirect()->route('retail.account.profile')->with('status', 'Profile updated.');
    }

    public function orders(Request $request): Response
    {
        $user = $request->user();
        $orders = $user->orders()
            ->where('sales_channel', 'retail')
            ->latest('placed_at')
            ->latest('id')
            ->paginate(15);

        $mapped = $orders->through(fn (Order $order) => [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'total' => number_format((float) $order->total, 2, '.', ''),
            'placedAt' => optional($order->placed_at)->format('M j, Y'),
            'items' => $order->items()->count(),
        ]);

        return Inertia::render('Account/Orders', [
            'orders' => $mapped->items(),
            'page' => ['currentPage' => $mapped->currentPage(), 'lastPage' => $mapped->lastPage()],
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('retail.home');
    }

    protected function claimSessionOrders(Request $request, User $user): void
    {
        if ($user->account_type !== 'retail') {
            return;
        }

        $ids = $request->session()->get('retail_order_ids', []);
        if (! empty($ids)) {
            DB::table('orders')
                ->where('sales_channel', 'retail')
                ->whereIn('id', $ids)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id, 'customer_email' => $user->email]);
        }
    }
}