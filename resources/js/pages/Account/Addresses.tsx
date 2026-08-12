import PublicShell from '@/components/PublicShell';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Address = { id: number; address: string; city: string; province: string; country: string; postalCode: string; phone: string; isPrimary: boolean };
type SharedProps = { flash?: { status?: string | null } };

export default function Addresses({ addresses }: { addresses: Address[] }) {
    const { flash } = usePage<SharedProps>().props;
    const [editing, setEditing] = useState<Address | null>(null);

    return (
        <PublicShell>
            <Head title="Shipping addresses" />
            <main className="mx-auto min-h-[40rem] max-w-5xl px-6 py-14">
                <div className="mb-8 flex flex-wrap justify-between gap-4">
                    <div>
                        <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">Account</p>
                        <h1 className="mt-3 text-4xl font-black tracking-tight">Shipping addresses</h1>
                    </div>
                    <div className="flex items-center gap-5">
                        <Link href="/account" className="text-sm font-bold">Back to account</Link>
                        <Link href="/orders" className="text-sm font-bold">Orders</Link>
                        <button onClick={() => router.post('/logout')} className="text-sm font-bold text-red-600">Sign out</button>
                    </div>
                </div>

                {flash?.status && <div className="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{flash.status}</div>}

                <div className="grid gap-8 lg:grid-cols-[1.1fr_1fr]">
                    <section className="grid gap-4">
                        <h2 className="text-lg font-bold">Saved addresses</h2>
                        {addresses.length === 0 ? (
                            <p className="rounded-2xl border border-dashed border-stone-300 p-8 text-sm text-stone-500">No shipping addresses saved yet. Add your first address using the form.</p>
                        ) : addresses.map(address => (
                            <article key={address.id} className="rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        {address.isPrimary && <span className="mb-2 inline-block rounded-full bg-amber-700 px-2.5 py-1 text-[10px] font-black tracking-wider text-white uppercase">Primary</span>}
                                        <p className="text-sm leading-6 text-stone-700">
                                            {address.address}<br />
                                            {address.city}, {address.province} {address.postalCode}<br />
                                            {address.country}<br />
                                            <span className="text-stone-500">Phone: {address.phone}</span>
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        <button onClick={() => setEditing(address)} className="rounded-full bg-stone-950 px-4 py-2 text-xs font-bold text-white transition hover:bg-stone-700">Edit</button>
                                        <button onClick={() => { if (confirm('Remove this address?')) router.delete(`/account/addresses/${address.id}`); }} className="rounded-full bg-white px-4 py-2 text-xs font-bold text-red-600 ring-1 ring-red-600/30 transition hover:bg-red-50">Remove</button>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </section>

                    <AddressForm key={editing?.id ?? 'new'} editing={editing} onDone={() => setEditing(null)} />
                </div>
            </main>
        </PublicShell>
    );
}

function AddressForm({ editing, onDone }: { editing: Address | null; onDone: () => void }) {
    const form = useForm({
        address: editing?.address ?? '',
        city: editing?.city ?? '',
        province: editing?.province ?? '',
        country: editing?.country ?? 'Canada',
        postal_code: editing?.postalCode ?? '',
        phone: editing?.phone ?? '',
        is_primary: editing?.isPrimary ?? false,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        if (editing) {
            form.put(`/account/addresses/${editing.id}`, { preserveScroll: true, onSuccess: onDone });
        } else {
            form.post('/account/addresses', { preserveScroll: true, onSuccess: () => { form.reset(); onDone(); } });
        }
    }

    return (
        <section className="rounded-2xl bg-white p-6 ring-1 ring-stone-200 sm:p-7">
            <h2 className="text-lg font-bold">{editing ? 'Edit address' : 'Add a new address'}</h2>
            <p className="mt-1 text-sm text-stone-500">Save multiple shipping addresses and pick one at checkout.</p>
            <form onSubmit={submit} className="mt-6 grid gap-4">
                <Field label="Street address" error={form.errors.address} value={form.data.address} onChange={v => form.setData('address', v)} />
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="City" error={form.errors.city} value={form.data.city} onChange={v => form.setData('city', v)} />
                    <Field label="Province / state" error={form.errors.province} value={form.data.province} onChange={v => form.setData('province', v)} />
                    <Field label="Country" error={form.errors.country} value={form.data.country} onChange={v => form.setData('country', v)} />
                    <Field label="Postal / ZIP code" error={form.errors.postal_code} value={form.data.postal_code} onChange={v => form.setData('postal_code', v)} />
                </div>
                <Field label="Phone" error={form.errors.phone} value={form.data.phone} onChange={v => form.setData('phone', v)} />
                <label className="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" checked={form.data.is_primary} onChange={e => form.setData('is_primary', e.target.checked)} className="accent-red-600" />
                    Set as primary shipping address
                </label>
                <div className="mt-2 flex gap-3">
                    <button disabled={form.processing} className="rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-black disabled:opacity-50">{form.processing ? 'Saving…' : editing ? 'Update address' : 'Save address'}</button>
                    {editing && <button type="button" onClick={onDone} className="rounded-full bg-white px-5 py-2.5 text-sm font-bold ring-1 ring-stone-300">Cancel</button>}
                </div>
            </form>
        </section>
    );
}

function Field({ label, error, value, onChange }: { label: string; error?: string; value: string; onChange: (value: string) => void }) {
    return (
        <label className="block">
            <span className="text-sm font-semibold">{label}</span>
            <input value={value} onChange={e => onChange(e.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 px-4 py-3" />
            {error && <span className="mt-1 block text-sm text-red-700">{error}</span>}
        </label>
    );
}