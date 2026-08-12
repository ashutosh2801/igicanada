import AccountShell from '../../components/AccountShell';
import { Head, Link } from '@inertiajs/react';

type Order = { id: number; number: string; status: string; paymentStatus: string; total: string; placedAt: string; items: number };

export default function Dashboard({ account, orderCount, recentOrders }: { account: { name: string; email: string; createdAt: string }; orderCount: number; recentOrders: Order[] }) {
    return (
        <>
            <Head title="My account" />
            <AccountShell title="My account">
                <p className="text-sm text-stone-600">Welcome back, <strong>{account.name}</strong>. You've placed {orderCount} {orderCount === 1 ? 'order' : 'orders'} with us.</p>

                <div className="mt-8 grid gap-4 sm:grid-cols-3">
                    <Card label="Profile" value={account.email} hint="Update name & email" href="/account/profile" />
                    <Card label="Addresses" value="Manage" hint="Saved shipping addresses" href="/account/addresses" />
                    <Card label="Orders" value={String(orderCount)} hint="View your order history" href="/account/orders" />
                </div>

                <section className="mt-10">
                    <div className="flex items-center justify-between">
                        <h2 className="font-display text-xl font-bold">Recent orders</h2>
                        <Link href="/account/orders" className="text-sm font-bold text-leather-700">View all <span aria-hidden="true">→</span></Link>
                    </div>
                    <div className="mt-4 grid gap-3">
                        {recentOrders.length === 0 ? (
                            <p className="rounded-3xl border border-dashed border-leather-900/15 p-8 text-sm text-stone-500">No orders yet. <Link href="/shop" className="font-bold text-leather-700">Start shopping</Link>.</p>
                        ) : recentOrders.map(order => (
                            <Link key={order.id} href={`/orders/${order.id}`} className="flex items-center justify-between gap-4 rounded-3xl bg-white p-5 ring-1 ring-leather-900/10 transition hover:ring-leather-900/30">
                                <div>
                                    <p className="font-bold">{order.number}</p>
                                    <p className="mt-0.5 text-xs text-stone-500">{order.placedAt} · {order.items} {order.items === 1 ? 'item' : 'items'} · {order.status.replace('_', ' ')}</p>
                                </div>
                                <p className="font-bold">${order.total} CAD</p>
                            </Link>
                        ))}
                    </div>
                </section>
            </AccountShell>
        </>
    );
}

function Card({ label, value, hint, href }: { label: string; value: string; hint: string; href: string }) {
    return (
        <Link href={href} className="block rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 transition hover:ring-leather-900/30">
            <p className="text-xs font-bold tracking-[0.16em] text-leather-700 uppercase">{label}</p>
            <p className="mt-2 truncate text-lg font-bold">{value}</p>
            <p className="mt-1 text-sm text-stone-500">{hint}</p>
        </Link>
    );
}