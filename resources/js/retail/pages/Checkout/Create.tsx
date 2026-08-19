import RetailShell from '../../components/RetailShell';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { cloneElement, FormEvent, ReactElement, useCallback, useEffect, useRef, useState } from 'react';

type PaymentMethod = { id: 'paypal' | 'manual'; label: string; description: string; available: boolean };
type Quote = { subtotal: string; shippingTotal: string | null; shippingName: string | null; tax: { label: string; amount: string; rate: number }; total: string };
type SharedProps = { flash?: { status?: string | null } };
type CartItem = { name: string; option: string; image: string | null; quantity: number; unitPrice: string; lineTotal: string };
type SavedAddress = { id: string; name: string; email: string | null; phone: string; address: string; city: string; province: string; country: string; country_code: string | null; postal_code: string; is_primary: boolean };

export default function Checkout({ subtotal, paypalConfigured, paymentMethods, defaultPaymentMethod, items, addresses }: { subtotal: string; paypalConfigured: boolean; paymentMethods: PaymentMethod[]; defaultPaymentMethod: 'paypal' | 'manual'; items: CartItem[]; addresses: SavedAddress[] }) {
    const { flash } = usePage<SharedProps>().props;
    const form = useForm({ name: '', email: '', phone: '', address: '', city: '', province: '', country: 'Canada', country_code: 'CA', postal_code: '', notes: '', payment_method: defaultPaymentMethod });
    const [selectedAddressId, setSelectedAddressId] = useState<string>(addresses.find(a => a.is_primary)?.id ?? '');

    function applyAddress(id: string) {
        setSelectedAddressId(id);
        if (!id) {
            form.setData(data => ({ ...data, name: '', email: data.email, phone: '', address: '', city: '', province: '', postal_code: '' }));
            return;
        }
        const address = addresses.find(a => a.id === id);
        if (!address) return;
        form.setData(data => ({
            ...data,
            name: address.name,
            email: address.email ?? data.email,
            phone: address.phone,
            address: address.address,
            city: address.city,
            province: address.province,
            country: address.country,
            country_code: address.country_code ?? 'CA',
            postal_code: address.postal_code,
        }));
    }
    const selectedMethod = paymentMethods.find(method => method.id === form.data.payment_method) ?? paymentMethods[0];
    const payingOnline = form.data.payment_method === 'paypal' && paypalConfigured;
    const [quote, setQuote] = useState<Quote | null>(null);
    const [quoting, setQuoting] = useState(false);
    const abortRef = useRef<AbortController | null>(null);

    const fetchQuote = useCallback(() => {
        abortRef.current?.abort();
        if (! form.data.province.trim()) {
            setQuote(null);
            return;
        }
        const controller = new AbortController();
        abortRef.current = controller;
        setQuoting(true);
        const xsrf = decodeURIComponent((document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/) || [])[1] || '');
        fetch('/checkout/quote', {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': xsrf },
            body: JSON.stringify({ country: form.data.country, country_code: form.data.country_code, province: form.data.province }),
        }).then((res) => res.json()).then((data: Quote) => setQuote(data)).catch((err) => {
            if (err.name !== 'AbortError') setQuote(null);
        }).finally(() => setQuoting(false));
    }, [form.data.country, form.data.country_code, form.data.province]);

    useEffect(() => {
        const handle = setTimeout(fetchQuote, 400);
        return () => clearTimeout(handle);
    }, [fetchQuote]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/checkout');
    }

    function countryChanged(country: string) {
        form.setData(data => ({ ...data, country, country_code: country === 'Canada' ? 'CA' : 'US' }));
    }

    return (
        <RetailShell>
            <Head title="Checkout" />
            <main className="mx-auto max-w-6xl px-6 py-14 lg:px-8 lg:py-20">
                <Link href="/cart" className="text-sm font-bold text-leather-700">← Return to bag</Link>
                <div className="mt-7 grid gap-10 lg:grid-cols-[1fr_22rem]">
                    <form onSubmit={submit} id="checkout-form" className="rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-9">
                        <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Secure checkout</p>
                        <h1 className="font-display mt-3 text-4xl font-bold">Shipping details</h1>
                        {addresses.length > 0 && (
                            <div className="mt-6 rounded-2xl bg-leather-50 p-5 ring-1 ring-leather-900/10">
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-sm font-bold">Saved address</span>
                                    <Link href="/account/addresses" className="text-xs font-bold text-leather-700">Manage addresses</Link>
                                </div>
                                <select value={selectedAddressId} onChange={e => applyAddress(e.target.value)} className="mt-3 w-full rounded-xl border-0 bg-white px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700">
                                    <option value="">Enter a new address</option>
                                    {addresses.map(address => <option key={address.id} value={address.id}>{address.name} — {address.address}, {address.city}</option>)}
                                </select>
                            </div>
                        )}
                        <div className="mt-8 grid gap-5 sm:grid-cols-2">
                            <Field label="Full name" error={form.errors.name}><input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></Field>
                            <Field label="Email" error={form.errors.email}><input type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                            <Field label="Phone" error={form.errors.phone}><input value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} required /></Field>
                            <Field label="Country" error={form.errors.country}><select value={form.data.country} onChange={e => countryChanged(e.target.value)}><option>Canada</option><option>United States</option></select></Field>
                            <div className="sm:col-span-2"><Field label="Street address" error={form.errors.address}><input value={form.data.address} onChange={e => form.setData('address', e.target.value)} required /></Field></div>
                            <Field label="City" error={form.errors.city}><input value={form.data.city} onChange={e => form.setData('city', e.target.value)} required /></Field>
                            <Field label="Province / State" error={form.errors.province}><input value={form.data.province} onChange={e => form.setData('province', e.target.value)} required /></Field>
                            <Field label="Postal / ZIP code" error={form.errors.postal_code}><input value={form.data.postal_code} onChange={e => form.setData('postal_code', e.target.value)} required /></Field>
                            <div className="sm:col-span-2"><Field label="Order notes (optional)" error={form.errors.notes}><textarea rows={4} value={form.data.notes} onChange={e => form.setData('notes', e.target.value)} /></Field></div>
                        </div>
                    </form>

                    <aside className="h-fit rounded-3xl bg-leather-100 p-7 ring-1 ring-leather-900/10">
                        <h2 className="font-display text-2xl font-bold">Your order</h2>
                        <ul className="mt-5 divide-y divide-leather-900/10">
                            {items.map((item, index) => (
                                <li key={index} className="flex gap-4 py-4">
                                    <div className="grid size-16 shrink-0 place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-leather-900/10">
                                        {item.image ? <img src={item.image} alt={item.name} className="h-full w-full object-contain p-1" /> : <span className="text-[10px] font-bold text-stone-400">No image</span>}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-bold">{item.name}</p>
                                        {item.option && <p className="text-xs text-stone-500">{item.option}</p>}
                                        <p className="mt-1 text-xs text-stone-500">Qty {item.quantity} × ${item.unitPrice}</p>
                                    </div>
                                    <p className="self-center text-sm font-bold">${item.lineTotal}</p>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-5 flex justify-between"><span>Products</span><strong>${subtotal} CAD</strong></div>
                        {quote ? (
                            <>
                                {quote.shippingTotal !== null
                                    ? <div className="mt-3 flex justify-between"><span>{quote.shippingName || 'Shipping'}</span><strong>${quote.shippingTotal} CAD</strong></div>
                                    : <p className="mt-3 text-xs text-red-700">Shipping unavailable for this destination.</p>}
                                {Number(quote.tax.amount) > 0 && <div className="mt-3 flex justify-between"><span>{quote.tax.label} ({(quote.tax.rate * 100).toFixed(2)}%)</span><strong>${quote.tax.amount} CAD</strong></div>}
                                <div className="mt-4 flex justify-between border-t border-leather-900/10 pt-4 text-base font-bold"><span>Total</span><strong>${quote.total} CAD</strong></div>
                            </>
                        ) : (
                            <p className="mt-4 text-sm leading-6 text-stone-600">{quoting ? 'Calculating shipping & tax…' : 'Enter your province/state to see shipping and tax.'}</p>
                        )}

                        {paymentMethods.length > 0 && (
                            <fieldset className="mt-6">
                                <legend className="text-sm font-bold">Payment method</legend>
                                <div className={`mt-3 grid gap-3 ${paymentMethods.length > 1 ? 'sm:grid-cols-2' : ''}`}>
                                    {paymentMethods.map(method => (
                                        <label key={method.id} className={`flex gap-3 rounded-2xl border px-4 py-4 transition ${form.data.payment_method === method.id ? 'border-leather-700 bg-white' : 'border-leather-900/15 bg-white'} ${paymentMethods.length > 1 ? 'cursor-pointer' : 'cursor-default'}`}>
                                            <input type="radio" name="payment_method" value={method.id} checked={form.data.payment_method === method.id} onChange={() => form.setData('payment_method', method.id)} className="mt-1 size-4 accent-leather-700" />
                                            <span className="text-sm">
                                                <span className="block font-bold">{method.label}</span>
                                                <span className="mt-1 block text-xs leading-5 text-stone-500">{method.description}</span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                {form.errors.payment_method && <p className="mt-2 text-xs text-red-700">{form.errors.payment_method}</p>}
                            </fieldset>
                        )}

                        {paymentMethods.length === 0 && (
                            <p className="mt-6 rounded-2xl bg-red-50 p-4 text-sm font-bold text-red-800">No payment methods are currently available. Please try again later or contact us.</p>
                        )}

                        {(form.errors.cart || form.errors.payment) && <p className="mt-5 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-800">{form.errors.cart || form.errors.payment}</p>}
                        {flash?.status && <p className="mt-5 rounded-xl bg-green-50 p-4 text-sm font-bold text-green-800">{flash.status}</p>}
                        <button type="submit" form="checkout-form" disabled={form.processing || paymentMethods.length === 0} className="mt-6 w-full rounded-full bg-leather-900 px-6 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Placing order…' : (payingOnline ? 'Continue securely with PayPal' : 'Place order')}</button>
                        <Link href="/orders/enquiry" className="mt-4 block text-center text-sm font-bold text-leather-700">Have a question about an order?</Link>
                    </aside>
                </div>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }>; }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-2 block text-xs text-red-700">{error}</span>}</label>;
}
