import RetailShell from '../../components/RetailShell';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement } from 'react';

type SharedProps = { flash?: { status?: string | null } };

export default function OrderEnquiry({ orderNumber }: { orderNumber?: string }) {
    const { flash } = usePage<SharedProps>().props;
    const form = useForm({ order_number: orderNumber ?? '', name: '', email: '', phone: '', message: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/orders/enquiry', { preserveScroll: true });
    }

    return (
        <RetailShell>
            <Head title="Order enquiry" />
            <main className="mx-auto max-w-3xl px-6 py-14 lg:px-8 lg:py-20">
                <Link href="/shop" className="text-sm font-bold text-leather-700">← Continue shopping</Link>
                <form onSubmit={submit} className="mt-7 rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-9">
                    <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Customer care</p>
                    <h1 className="font-display mt-3 text-4xl font-bold">Order enquiry</h1>
                    <p className="mt-3 text-sm leading-6 text-stone-600">Have a question about your order? Share your order number and message and our team will get back to you.</p>

                    {flash?.status && <p className="mt-5 rounded-xl bg-green-50 p-4 text-sm font-bold text-green-800">{flash.status}</p>}

                    <div className="mt-8 grid gap-5 sm:grid-cols-2">
                        <Field label="Order number" error={form.errors.order_number}><input value={form.data.order_number} onChange={e => form.setData('order_number', e.target.value)} placeholder="LW-..." required /></Field>
                        <Field label="Full name" error={form.errors.name}><input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></Field>
                        <Field label="Email" error={form.errors.email}><input type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                        <Field label="Phone (optional)" error={form.errors.phone}><input value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} /></Field>
                        <div className="sm:col-span-2"><Field label="How can we help?" error={form.errors.message}><textarea rows={5} value={form.data.message} onChange={e => form.setData('message', e.target.value)} required /></Field></div>
                    </div>

                    <button disabled={form.processing} className="mt-8 w-full rounded-full bg-leather-900 px-6 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Sending…' : 'Send enquiry'}</button>
                </form>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }>; }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-2 block text-xs text-red-700">{error}</span>}</label>;
}