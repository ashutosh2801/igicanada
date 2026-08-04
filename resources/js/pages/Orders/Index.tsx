import PublicShell from '@/components/PublicShell';
import { Head, Link } from '@inertiajs/react';

type Order = { id: number; number: string; status: string; paymentStatus: string; total: string; currency: string; placedAt: string };

export default function Index({ orders }: { orders: Order[] }) {
    return (
        <PublicShell>
            <Head title="Orders" />
            <main className="mx-auto min-h-[40rem] max-w-5xl px-6 py-14">
                <div className="flex items-end justify-between gap-5"><div><p className="text-xs font-black tracking-[0.18em] text-red-600 uppercase">Wholesale account</p><h1 className="mt-2 text-4xl font-black">Orders</h1></div><Link href="/account" className="text-sm font-bold">My account</Link></div>
                <div className="mt-8 divide-y rounded-2xl bg-white ring-1 ring-stone-200">{orders.map(order => <Link key={order.id} href={'/orders/' + order.id} className="flex items-center justify-between gap-5 p-5 transition hover:bg-red-50"><div><p className="font-bold">{order.number}</p><p className="mt-1 text-sm text-stone-500">{order.placedAt} · {order.status.replace('_', ' ')}</p></div><strong>{'$' + order.total + ' ' + order.currency}</strong></Link>)}{orders.length === 0 && <p className="p-10 text-center text-stone-500">No orders yet.</p>}</div>
            </main>
        </PublicShell>
    );
}
