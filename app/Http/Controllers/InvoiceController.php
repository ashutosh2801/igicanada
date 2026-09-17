<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function __invoke(Request $request, Order $order): Response
    {
        $user = $request->user('web') ?? $request->user('admin');
        $isAdmin = $user->account_type === 'admin' && $user->approval_status === 'approved';
        abort_unless($order->user_id === $user->id || $isAdmin, 404);
        $order->loadMissing('user.resellerProfile', 'items');

        return Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'company' => config('commerce.company'),
        ])
            ->setPaper('letter')
            ->download("IGI-Canada-{$order->order_number}.pdf");
    }
}
