import PublicShell from '@/components/PublicShell';
import { Head, Link, router } from '@inertiajs/react';

type Item = {
    id: number;
    product: string;
    slug: string;
    sku: string | null;
    option: string;
    image: string | null;
    quantity: number;
    minimumQuantity: number;
    maximumQuantity: number;
    unitPrice: string;
    lineTotal: string;
};

export default function Cart({ items, subtotal }: { items: Item[]; subtotal: string }) {
    function update(item: Item, quantity: number) {
        router.put('/cart/items/' + item.id, { quantity }, { preserveScroll: true });
    }

    return (
        <PublicShell>
            <Head title="Wholesale cart" />
                <main className="mx-auto min-h-[40rem] max-w-6xl px-6 py-14">
                    <Link href="/catalogue" className="mb-7 inline-block text-sm font-bold text-red-600">← Continue shopping</Link>
                    <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">Wholesale order</p>
                    <h1 className="mt-3 text-4xl font-black tracking-tight">Your cart</h1>

                    {items.length === 0 ? (
                        <div className="mt-10 rounded-2xl bg-white p-12 text-center ring-1 ring-stone-200">
                            <p className="text-stone-600">Your cart is empty.</p>
                            <Link href="/catalogue" className="mt-5 inline-flex rounded-full bg-stone-950 px-5 py-3 text-sm font-bold text-white">Browse products</Link>
                        </div>
                    ) : (
                        <div className="mt-10 grid gap-8 lg:grid-cols-[1fr_320px]">
                            <section className="divide-y divide-stone-200 rounded-2xl bg-white ring-1 ring-stone-200">
                                {items.map((item) => (
                                    <article key={item.id} className="grid grid-cols-[80px_1fr] gap-5 p-5 sm:grid-cols-[96px_1fr_auto]">
                                        <div className="aspect-square overflow-hidden rounded-xl bg-stone-50">{item.image && <img src={item.image} alt="" className="h-full w-full object-contain p-2" />}</div>
                                        <div>
                                            <p className="text-xs font-semibold text-stone-500">{item.sku} · {item.option}</p>
                                            <Link href={'/catalogue/' + item.slug} className="mt-1 block font-bold">{item.product}</Link>
                                            <p className="mt-2 text-sm text-stone-500">{'$' + item.unitPrice + ' CAD each'}</p>
                                            <div className="mt-4 flex items-center gap-3">
                                                <input type="number" min={item.minimumQuantity} max={item.maximumQuantity} defaultValue={item.quantity} onBlur={(event) => update(item, Number(event.target.value))} className="w-24 rounded-lg border border-stone-300 px-3 py-2" aria-label={'Quantity for ' + item.product} />
                                                <button onClick={() => router.delete('/cart/items/' + item.id, { preserveScroll: true })} className="text-sm font-semibold text-red-700">Remove</button>
                                            </div>
                                        </div>
                                        <p className="font-black sm:text-right">{'$' + item.lineTotal}</p>
                                    </article>
                                ))}
                            </section>
                            <aside className="h-fit rounded-2xl bg-stone-950 p-6 text-white">
                                <div className="flex justify-between"><span className="text-stone-400">Subtotal</span><strong>{'$' + subtotal + ' CAD'}</strong></div>
                                <p className="mt-4 border-t border-white/10 pt-4 text-xs leading-5 text-stone-400">Taxes and shipping will be calculated during checkout.</p>
                                <Link href="/checkout" className="mt-6 block w-full rounded-xl bg-amber-700 px-5 py-3 text-center font-bold">Continue to checkout</Link>
                            </aside>
                        </div>
                    )}
                </main>
        </PublicShell>
    );
}
