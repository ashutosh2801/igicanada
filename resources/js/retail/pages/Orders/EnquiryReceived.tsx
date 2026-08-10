import RetailShell from '../../components/RetailShell';
import { Head, Link } from '@inertiajs/react';

type Item = { name: string; option: string; quantity: number; unit_price: string; line_total: string };
type Summary = {
    name: string;
    email: string;
    phone: string;
    address: string;
    city: string;
    province: string;
    postal_code: string;
    country: string;
    notes?: string | null;
    items: Item[];
    subtotal: string;
    total: string;
};

export default function EnquiryReceived({ summary }: { summary: Summary }) {
    return (
        <RetailShell>
            <Head title="Order enquiry received" />
            <main className="mx-auto max-w-5xl px-6 py-14 lg:px-8 lg:py-20">
                <div className="rounded-[2rem] bg-white p-7 ring-1 ring-leather-900/10 sm:p-10">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-start">
                        <div>
                            <p className="text-xs font-bold tracking-[0.2em] text-green-700 uppercase">Enquiry received</p>
                            <h1 className="font-display mt-3 text-4xl font-bold">Thank you. Your order enquiry has been sent and our team will reply shortly.</h1>
                            <p className="mt-3 text-stone-600">A confirmation has been emailed to <strong>{summary.email}</strong>.</p>
                        </div>
                        <span className="w-fit rounded-full bg-leather-100 px-4 py-2 text-sm font-bold">Awaiting reply</span>
                    </div>

                    <div className="mt-10 grid gap-10 md:grid-cols-[1fr_18rem]">
                        <div>
                            <h2 className="font-display text-2xl font-bold">Items</h2>
                            <div className="mt-4 divide-y divide-leather-900/10">
                                {summary.items.map((item, index) => (
                                    <div key={index} className="flex justify-between gap-5 py-4">
                                        <div>
                                            <p className="font-bold">{item.name}</p>
                                            <p className="text-sm text-stone-500">{item.option} · Qty {item.quantity}</p>
                                        </div>
                                        <p className="font-bold">${item.line_total}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <aside>
                            <h2 className="font-display text-2xl font-bold">Summary</h2>
                            <dl className="mt-4 grid gap-3 text-sm">
                                <div className="flex justify-between"><dt>Subtotal</dt><dd>${summary.subtotal} CAD</dd></div>
                                <div className="mt-2 flex justify-between border-t border-leather-900/10 pt-4 text-base font-bold"><dt>Estimated total</dt><dd>${summary.total} CAD</dd></div>
                            </dl>
                        </aside>
                    </div>

                    <div className="mt-10 border-t border-leather-900/10 pt-7">
                        <h2 className="font-display text-xl font-bold">Shipping to</h2>
                        <p className="mt-3 text-sm leading-6 text-stone-600">
                            {summary.name}<br />
                            {summary.address}<br />
                            {summary.city}, {summary.province} {summary.postal_code}<br />
                            {summary.country}<br />
                            {summary.phone && <>Phone: {summary.phone}</>}
                        </p>
                        {summary.notes && <p className="mt-4 text-sm leading-6 text-stone-600"><strong>Your notes:</strong> {summary.notes}</p>}
                    </div>

                    <div className="mt-9 flex flex-wrap gap-4">
                        <Link href="/shop" className="inline-flex rounded-full bg-leather-100 px-5 py-2.5 text-sm font-bold text-leather-900">Continue shopping</Link>
                        <Link href="/orders/enquiry" className="inline-flex rounded-full bg-white px-5 py-2.5 text-sm font-bold text-leather-900 ring-1 ring-leather-900/15">Ask about an order</Link>
                    </div>
                </div>
            </main>
        </RetailShell>
    );
}
