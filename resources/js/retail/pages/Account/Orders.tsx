import AccountShell from '../../components/AccountShell';
import { Head, Link } from '@inertiajs/react';

type Order = { id: number; number: string; status: string; paymentStatus: string; total: string; placedAt: string; items: number };

export default function Orders({ orders, page }: { orders: Order[]; page: { currentPage: number; lastPage: number } }) {
    return (
        <>
            <Head title="Order history" />
            <AccountShell title="Order history">
                <div className="grid gap-3">
                    {orders.length === 0 ? (
                        <p className="rounded-3xl border border-dashed border-leather-900/15 p-8 text-sm text-stone-500">No orders yet. <Link href="/shop" className="font-bold text-leather-700">Start shopping</Link>.</p>
                    ) : orders.map(order => (
                        <Link key={order.id} href={`/orders/${order.id}`} className="flex items-center justify-between gap-4 rounded-3xl bg-white p-5 ring-1 ring-leather-900/10 transition hover:ring-leather-900/30">
                            <div>
                                <p className="font-bold">{order.number}</p>
                                <p className="mt-0.5 text-xs text-stone-500">{order.placedAt} · {order.items} {order.items === 1 ? 'item' : 'items'} · {order.status.replace('_', ' ')}</p>
                            </div>
                            <div className="text-right">
                                <p className="font-bold">${order.total} CAD</p>
                                <span className="text-xs font-bold capitalize text-leather-700">{order.paymentStatus.replace('_', ' ')}</span>
                            </div>
                        </Link>
                    ))}
                </div>
                {page.lastPage > 1 && (
                    <div className="mt-6 flex justify-center gap-3 text-sm font-bold">
                        {page.currentPage > 1 && <Link href={`?page=${page.currentPage - 1}`} className="rounded-full bg-white px-4 py-2 ring-1 ring-leather-900/15">← Previous</Link>}
                        <span className="px-4 py-2">Page {page.currentPage} of {page.lastPage}</span>
                        {page.currentPage < page.lastPage && <Link href={`?page=${page.currentPage + 1}`} className="rounded-full bg-white px-4 py-2 ring-1 ring-leather-900/15">Next →</Link>}
                    </div>
                )}
            </AccountShell>
        </>
    );
}