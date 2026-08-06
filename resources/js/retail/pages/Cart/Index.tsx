import RetailShell from '../../components/RetailShell';
import { Head, Link, router } from '@inertiajs/react';

type Item = { id: number; product: string; slug: string; option: string; image: string | null; quantity: number; maximumQuantity: number; unitPrice: string; lineTotal: string };

export default function Cart({ items, subtotal }: { items: Item[]; subtotal: string }) {
    return (
        <RetailShell>
            <Head title="Your bag" />
            <main className="mx-auto max-w-6xl px-6 py-14 lg:px-8 lg:py-20">
                <p className="text-xs font-bold tracking-[0.22em] text-leather-700 uppercase">Your selection</p>
                <h1 className="font-display mt-3 text-5xl font-bold tracking-tight">Shopping bag</h1>

                {items.length > 0 ? <div className="mt-10 grid gap-10 lg:grid-cols-[1fr_22rem]">
                    <div className="divide-y divide-leather-900/10 rounded-3xl bg-white px-5 ring-1 ring-leather-900/10 sm:px-7">
                        {items.map(item => <article key={item.id} className="grid grid-cols-[6rem_1fr] gap-5 py-7 sm:grid-cols-[7rem_1fr_auto]">
                            <Link href={'/products/' + item.slug} className="aspect-square overflow-hidden rounded-xl bg-leather-50">{item.image ? <img src={item.image} alt={item.product} className="h-full w-full object-contain p-2" /> : null}</Link>
                            <div>
                                <h2 className="font-display text-xl font-bold"><Link href={'/products/' + item.slug}>{item.product}</Link></h2>
                                <p className="mt-1 text-sm text-stone-500">{item.option}</p>
                                <p className="mt-2 font-bold text-leather-700">${item.unitPrice} each</p>
                                <button onClick={() => router.delete(`/cart/items/${item.id}`, { preserveScroll: true })} className="mt-4 text-xs font-bold text-stone-500 underline">Remove</button>
                            </div>
                            <div className="col-start-2 flex items-center justify-between gap-5 sm:col-start-auto sm:block sm:text-right">
                                <select value={item.quantity} onChange={event => router.put(`/cart/items/${item.id}`, { quantity: Number(event.target.value) }, { preserveScroll: true })} className="rounded-full bg-leather-50 px-4 py-2 text-sm font-bold ring-1 ring-leather-900/10">
                                    {Array.from({ length: Math.min(item.maximumQuantity, 20) }, (_, index) => index + 1).map(quantity => <option key={quantity} value={quantity}>{quantity}</option>)}
                                </select>
                                <p className="mt-3 font-bold sm:mt-5">${item.lineTotal}</p>
                            </div>
                        </article>)}
                    </div>

                    <aside className="h-fit rounded-3xl bg-leather-100 p-7 ring-1 ring-leather-900/10">
                        <h2 className="font-display text-2xl font-bold">Order summary</h2>
                        <div className="mt-6 flex justify-between border-b border-leather-900/10 pb-5"><span>Subtotal</span><strong>${subtotal} CAD</strong></div>
                        <p className="mt-4 text-sm leading-6 text-stone-600">Shipping is calculated from your destination during checkout.</p>
                        <Link href="/checkout" className="mt-7 flex justify-center rounded-full bg-leather-900 px-6 py-3 font-bold text-white transition hover:bg-leather-700">Continue to checkout</Link>
                        <Link href="/shop" className="mt-4 block text-center text-sm font-bold text-leather-700">Continue shopping</Link>
                    </aside>
                </div> : <div className="mt-12 rounded-3xl border border-dashed border-leather-900/25 bg-white/50 p-16 text-center"><p className="font-display text-3xl font-bold">Your bag is empty</p><p className="mt-3 text-stone-600">Discover leather goods made for everyday carry.</p><Link href="/shop" className="mt-7 inline-flex rounded-full bg-leather-900 px-6 py-3 font-bold text-white">Start shopping</Link></div>}
            </main>
        </RetailShell>
    );
}
