import PublicShell from '@/components/PublicShell';
import { Head, Link, router } from '@inertiajs/react';

type Order = { id: number; number: string; status: string; paymentStatus: string; currency: string; subtotal: string; shippingTotal: string; shippingMethod: string | null; shippingService: string | null; taxTotal: string; total: string; placedAt: string; notes: string | null; address: Record<string, string | null>; items: { product_name: string; sku: string | null; option: string | null; quantity: number; unit_price: string; line_total: string }[] };

export default function Show({ order, payment, flash, errors }: { order: Order; payment: { paypalEnabled: boolean; canPay: boolean }; flash?: { status?: string }; errors?: { payment?: string } }) {
    return (
        <PublicShell>
            <Head title={order.number} />
            <main className="mx-auto min-h-[40rem] max-w-5xl px-6 py-14">
                <div className="mb-7 flex justify-end gap-5 text-sm font-bold"><a href={'/orders/' + order.id + '/invoice'}>Download PDF</a><Link href="/orders">All orders</Link></div>
                {flash?.status && <p className="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{flash.status}</p>}
                {errors?.payment && <p className="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">{errors.payment}</p>}
                <p className="text-sm font-bold text-red-600">ORDER REQUEST</p><h1 className="mt-2 text-4xl font-black">{order.number}</h1><p className="mt-3 capitalize text-stone-600">{order.status.replace('_', ' ')} · Payment {order.paymentStatus}</p>
                <div className="mt-8 grid gap-7 lg:grid-cols-[1fr_300px]"><section className="divide-y rounded-2xl bg-white ring-1 ring-stone-200">{order.items.map((item, index) => <div key={index} className="flex justify-between gap-5 p-5"><div><p className="font-bold">{item.product_name}</p><p className="text-sm text-stone-500">{item.sku} {item.option && '· ' + item.option} · Qty {item.quantity}</p></div><strong>{'$' + item.line_total}</strong></div>)}</section><aside className="h-fit rounded-2xl bg-black p-6 text-white"><Row label="Products" value={order.subtotal} /><Row label="Shipping" value={order.shippingTotal} />{order.shippingMethod && <p className="pb-2 text-xs text-white/60">{order.shippingMethod}{order.shippingService && ' · ' + order.shippingService}</p>}<Row label="Tax" value={order.taxTotal} /><div className="mt-4 border-t border-white/10 pt-4"><Row label="Current total" value={order.total} strong /></div>{payment.canPay ? <button onClick={() => router.post('/orders/' + order.id + '/paypal')} className="mt-6 w-full rounded-xl bg-red-600 px-5 py-3 font-bold text-white">Pay securely with PayPal</button> : <p className="mt-5 text-xs leading-5 text-white/70">{order.paymentStatus === 'paid' ? 'Payment received.' : order.status === 'awaiting_quote' ? 'Applicable tax is pending staff review. You will receive the final invoice before payment.' : !payment.paypalEnabled ? 'Online payment is not configured yet.' : 'Online payment is unavailable for this order.'}</p>}</aside></div>
            </main>
        </PublicShell>
    );
}

function Row({ label, value, strong = false }: { label: string; value: string; strong?: boolean }) {
    return <div className={'flex justify-between py-1 ' + (strong ? 'font-bold' : 'text-sm')}><span className="text-white/70">{label}</span><span>{'$' + value + ' CAD'}</span></div>;
}
