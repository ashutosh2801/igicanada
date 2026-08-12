import AccountShell from '../../components/AccountShell';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement, useState } from 'react';

type Address = { id: string; name: string; email: string | null; phone: string; address: string; city: string; province: string; country: string; country_code: string | null; postal_code: string; is_primary: boolean };
type SharedProps = { flash?: { status?: string | null } };

export default function Addresses({ addresses, guest }: { addresses: Address[]; guest?: boolean }) {
    const { flash } = usePage<SharedProps>().props;
    const [editing, setEditing] = useState<Address | null>(null);

    return (
        <>
            <Head title="Shipping addresses" />
            <AccountShell title="Shipping addresses">
                <p className="text-sm text-stone-600">Save multiple shipping addresses and pick one at checkout.</p>

                {flash?.status && <div className="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{flash.status}</div>}

                <div className="grid gap-6">
                    <section className="grid gap-3">
                        <h2 className="font-display text-xl font-bold">Saved addresses</h2>
                        {addresses.length === 0 ? (
                            <p className="rounded-3xl border border-dashed border-leather-900/15 p-8 text-sm text-stone-500">No shipping addresses saved yet. Add your first address using the form.</p>
                        ) : addresses.map(address => (
                            <article key={address.id} className="rounded-3xl bg-white p-6 ring-1 ring-leather-900/10">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        {address.is_primary && <span className="mb-2 inline-block rounded-full bg-leather-700 px-2.5 py-1 text-[10px] font-black tracking-wider text-white uppercase">Primary</span>}
                                        {address.name && <p className="font-bold">{address.name}</p>}
                                        <p className="mt-1 text-sm leading-6 text-stone-600">
                                            {address.address}<br />
                                            {address.city}, {address.province} {address.postal_code}<br />
                                            {address.country}<br />
                                            <span className="text-stone-500">Phone: {address.phone}</span>
                                            {address.email && <><br /><span className="text-stone-500">{address.email}</span></>}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        <button onClick={() => setEditing(address)} className="rounded-full bg-leather-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-leather-700">Edit</button>
                                        <button onClick={() => { if (confirm('Remove this address?')) router.delete(`/addresses/${address.id}`); }} className="rounded-full bg-white px-4 py-2 text-xs font-bold text-red-700 ring-1 ring-red-700/20 transition hover:bg-red-50">Remove</button>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </section>

                    <AddressForm key={editing?.id ?? 'new'} editing={editing} onDone={() => setEditing(null)} />
                </div>

                {guest && (
                    <div className="mt-6 rounded-2xl bg-leather-100 p-5 text-sm">
                        <p className="font-bold">Save addresses between visits</p>
                        <p className="mt-1 text-stone-600">These addresses are stored in this browser session only. <Link href="/account/register" className="font-bold text-leather-700">Create an account</Link> or <Link href="/account/login" className="font-bold text-leather-700">sign in</Link> to keep them safe on every device.</p>
                    </div>
                )}
            </AccountShell>
        </>
    );
}

function AddressForm({ editing, onDone }: { editing: Address | null; onDone: () => void }) {
    const form = useForm({
        name: editing?.name ?? '',
        email: editing?.email ?? '',
        phone: editing?.phone ?? '',
        address: editing?.address ?? '',
        city: editing?.city ?? '',
        province: editing?.province ?? '',
        country: editing?.country ?? 'Canada',
        country_code: editing?.country_code ?? 'CA',
        postal_code: editing?.postal_code ?? '',
        is_primary: editing?.is_primary ?? false,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        if (editing) {
            form.put(`/addresses/${editing.id}`, { preserveScroll: true, onSuccess: onDone });
        } else {
            form.post('/addresses', { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } });
        }
    }

    function countryChanged(country: string) {
        form.setData(data => ({ ...data, country, country_code: country === 'Canada' ? 'CA' : 'US' }));
    }

    return (
        <section className="rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-8">
            <h2 className="font-display text-xl font-bold">{editing ? 'Edit address' : 'Add a new address'}</h2>
            <form onSubmit={submit} className="mt-6 grid gap-4 sm:grid-cols-2">
                <Field label="Full name" error={form.errors.name}><input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></Field>
                <Field label="Email (optional)" error={form.errors.email}><input type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} /></Field>
                <Field label="Phone" error={form.errors.phone}><input value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} required /></Field>
                <Field label="Country" error={form.errors.country}><select value={form.data.country} onChange={e => countryChanged(e.target.value)}><option>Canada</option><option>United States</option></select></Field>
                <div className="sm:col-span-2"><Field label="Street address" error={form.errors.address}><input value={form.data.address} onChange={e => form.setData('address', e.target.value)} required /></Field></div>
                <div className="grid gap-4 sm:col-span-2 sm:grid-cols-3">
                    <Field label="City" error={form.errors.city}><input value={form.data.city} onChange={e => form.setData('city', e.target.value)} required /></Field>
                    <Field label="Province / State" error={form.errors.province}><input value={form.data.province} onChange={e => form.setData('province', e.target.value)} required /></Field>
                    <Field label="Postal / ZIP code" error={form.errors.postal_code}><input value={form.data.postal_code} onChange={e => form.setData('postal_code', e.target.value)} required /></Field>
                </div>
                <label className="flex items-center gap-2 text-sm font-bold sm:col-span-2">
                    <input type="checkbox" checked={form.data.is_primary} onChange={e => form.setData('is_primary', e.target.checked)} className="size-4 accent-leather-700" />
                    Set as primary shipping address
                </label>
                <div className="flex gap-3 sm:col-span-2">
                    <button disabled={form.processing} className="rounded-full bg-leather-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Saving…' : editing ? 'Update address' : 'Save address'}</button>
                    {editing && <button type="button" onClick={onDone} className="rounded-full bg-white px-5 py-3 text-sm font-bold ring-1 ring-leather-900/15">Cancel</button>}
                </div>
            </form>
        </section>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }> }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-2 block text-xs text-red-700">{error}</span>}</label>;
}