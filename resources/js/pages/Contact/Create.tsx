import PublicShell from '@/components/PublicShell';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

type Company = { name: string; address: string; city: string; country: string; phone: string; email: string };
type SharedProps = { flash?: { status?: string | null } };

const fieldClass = 'mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 outline-none transition focus:border-amber-800 focus:ring-2 focus:ring-amber-800/10';

export default function Create({ company }: { company: Company }) {
    const { flash } = usePage<SharedProps>().props;
    const form = useForm({ name: '', email: '', phone: '', company: '', subject: '', message: '', website: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/contact', { preserveScroll: true, onSuccess: () => form.reset() });
    }

    return (
        <PublicShell>
            <Head title="Contact IGI Canada" />
            <main className="mx-auto grid max-w-7xl gap-12 px-6 py-16 lg:grid-cols-[0.75fr_1.25fr] lg:px-8 lg:py-24">
                <section>
                    <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">Let’s talk</p>
                    <h1 className="mt-3 text-5xl font-black tracking-tight">How can we help?</h1>
                    <p className="mt-5 leading-7 text-stone-600">Questions about wholesale accounts, products, orders or shipping are welcome.</p>
                    <address className="mt-10 not-italic leading-7">
                        <strong>{company.name}</strong><br />
                        {company.address}<br />{company.city}<br />{company.country}<br />
                        <a className="mt-4 inline-block font-semibold text-amber-800" href={'tel:' + company.phone.replace(/[^+\d]/g, '')}>{company.phone}</a><br />
                        <a className="font-semibold text-amber-800" href={'mailto:' + company.email}>{company.email}</a>
                    </address>
                </section>
                <section className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-stone-900/10 sm:p-10">
                    {flash?.status && <div role="status" className="mb-7 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{flash.status}</div>}
                    <form onSubmit={submit} className="grid gap-5 sm:grid-cols-2">
                        <Field label="Name" error={form.errors.name}><input className={fieldClass} value={form.data.name} onChange={e => form.setData('name', e.target.value)} required /></Field>
                        <Field label="Email" error={form.errors.email}><input className={fieldClass} type="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                        <Field label="Company" error={form.errors.company}><input className={fieldClass} value={form.data.company} onChange={e => form.setData('company', e.target.value)} /></Field>
                        <Field label="Phone" error={form.errors.phone}><input className={fieldClass} value={form.data.phone} onChange={e => form.setData('phone', e.target.value)} /></Field>
                        <div className="sm:col-span-2"><Field label="Subject" error={form.errors.subject}><input className={fieldClass} value={form.data.subject} onChange={e => form.setData('subject', e.target.value)} /></Field></div>
                        <div className="hidden" aria-hidden="true"><label>Website<input tabIndex={-1} autoComplete="off" value={form.data.website} onChange={e => form.setData('website', e.target.value)} /></label></div>
                        <div className="sm:col-span-2"><Field label="Message" error={form.errors.message}><textarea rows={7} className={fieldClass} value={form.data.message} onChange={e => form.setData('message', e.target.value)} required /></Field></div>
                        <div className="sm:col-span-2"><button disabled={form.processing} className="rounded-full bg-stone-950 px-7 py-3 font-bold text-white disabled:opacity-50">{form.processing ? 'Sending…' : 'Send enquiry'}</button></div>
                    </form>
                </section>
            </main>
        </PublicShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="block text-sm font-semibold">{label}{children}{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}
