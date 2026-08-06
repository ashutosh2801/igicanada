import RetailShell from '../../components/RetailShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { cloneElement, FormEvent, ReactElement } from 'react';

export default function Checkout({ subtotal, paypalConfigured }: { subtotal: string; paypalConfigured: boolean }) {
    const form = useForm({ name: '', email: '', phone: '', address: '', city: '', province: '', country: 'Canada', country_code: 'CA', postal_code: '', notes: '' });

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
                    <form onSubmit={submit} className="rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-9">
                        <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Secure checkout</p>
                        <h1 className="font-display mt-3 text-4xl font-bold">Shipping details</h1>
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
                        {(form.errors.cart || form.errors.payment) && <p className="mt-5 rounded-xl bg-red-50 p-4 text-sm font-bold text-red-800">{form.errors.cart || form.errors.payment}</p>}
                        <button disabled={form.processing} className="mt-8 w-full rounded-full bg-leather-900 px-6 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Placing order…' : (paypalConfigured ? 'Continue securely with PayPal' : 'Place order')}</button>
                    </form>

                    <aside className="h-fit rounded-3xl bg-leather-100 p-7 ring-1 ring-leather-900/10">
                        <h2 className="font-display text-2xl font-bold">Your order</h2>
                        <div className="mt-6 flex justify-between border-b border-leather-900/10 pb-5"><span>Products</span><strong>${subtotal} CAD</strong></div>
                        <p className="mt-4 text-sm leading-6 text-stone-600">Standard shipping will be calculated when you place the order.</p>
                        <div className="mt-5 rounded-2xl bg-white/70 p-4 text-sm leading-6 text-stone-600">{paypalConfigured ? 'You will be redirected to PayPal for secure payment.' : 'Online payment is temporarily unavailable. We will email payment instructions after receiving your order.'}</div>
                    </aside>
                </div>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }>; }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-2 block text-xs text-red-700">{error}</span>}</label>;
}
