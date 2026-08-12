import RetailShell from '../../components/RetailShell';
import { Head, Link, router } from '@inertiajs/react';

type Order = { id: number; number: string; status: string; paymentStatus: string; subtotal: string; shippingTotal: string; shippingService: string; taxTotal: string; taxLabel: string; total: string; currency: string; address: Record<string, string>; items: { product_name: string; option: string; quantity: number; unit_price: string; line_total: string }[] };

export default function OrderShow({ order, payment }: { order: Order; payment: { paypalEnabled: boolean; canPay: boolean } }) {
    return (
        <RetailShell>
            <Head title={`Order ${order.number}`} />
            <main className="mx-auto max-w-5xl px-6 py-14 lg:px-8 lg:py-20">
                <div className="rounded-[2rem] bg-white p-7 ring-1 ring-leather-900/10 sm:p-10">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                        <div><p className="text-xs font-bold tracking-[0.2em] text-green-700 uppercase">Order received</p><h1 className="font-display mt-3 text-4xl font-bold">Thank you for your order.</h1><p className="mt-3 text-stone-600">Confirmation number <strong>{order.number}</strong></p></div>
                        <span className="w-fit rounded-full bg-leather-100 px-4 py-2 text-sm font-bold capitalize">{order.paymentStatus.replace('_', ' ')}</span>
                    </div>

                    {payment.canPay && <div className="mt-8 rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-900/10"><p className="font-bold">Payment is still required.</p><p className="mt-1 text-sm text-stone-600">Complete your order securely through PayPal.</p><button onClick={() => router.post(`/orders/${order.id}/paypal`)} className="mt-4 rounded-full bg-leather-900 px-5 py-2.5 text-sm font-bold text-white">Pay with PayPal</button></div>}

                    <div className="mt-10 grid gap-10 md:grid-cols-[1fr_18rem]">
                        <div><h2 className="font-display text-2xl font-bold">Items</h2><div className="mt-4 divide-y divide-leather-900/10">{order.items.map((item, index) => <div key={index} className="flex justify-between gap-5 py-4"><div><p className="font-bold">{item.product_name}</p><p className="text-sm text-stone-500">{item.option} · Qty {item.quantity}</p></div><p className="font-bold">${item.line_total}</p></div>)}</div></div>
                        <aside><h2 className="font-display text-2xl font-bold">Summary</h2><dl className="mt-4 grid gap-3 text-sm"><div className="flex justify-between"><dt>Subtotal</dt><dd>${order.subtotal}</dd></div><div className="flex justify-between"><dt>{order.shippingService || 'Shipping'}</dt><dd>${order.shippingTotal}</dd></div><div className="flex justify-between"><dt>{order.taxLabel || 'Tax'}</dt><dd>${order.taxTotal}</dd></div><div className="mt-2 flex justify-between border-t border-leather-900/10 pt-4 text-base font-bold"><dt>Total</dt><dd>${order.total} {order.currency}</dd></div></dl></aside>
                    </div>

                    <div className="mt-10 border-t border-leather-900/10 pt-7"><h2 className="font-display text-xl font-bold">Shipping to</h2><p className="mt-3 text-sm leading-6 text-stone-600">{order.address.name}<br />{order.address.address}<br />{order.address.city}, {order.address.province} {order.address.postal_code}<br />{order.address.country}</p></div>
                    <div className="mt-9 flex flex-wrap gap-4">
                        <Link href="/shop" className="inline-flex rounded-full bg-leather-100 px-5 py-2.5 text-sm font-bold text-leather-900">Continue shopping</Link>
                        <Link href={`/orders/enquiry?order=${encodeURIComponent(order.number)}`} className="inline-flex rounded-full bg-white px-5 py-2.5 text-sm font-bold text-leather-900 ring-1 ring-leather-900/15">Ask about this order</Link>
                    </div>
                </div>
            </main>
        </RetailShell>
    );
}
