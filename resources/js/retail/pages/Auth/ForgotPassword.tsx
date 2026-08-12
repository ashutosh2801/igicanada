import RetailShell from '../../components/RetailShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const form = useForm({ email: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/account/forgot-password');
    }

    return (
        <RetailShell>
            <Head title="Reset password" />
            <main className="mx-auto max-w-md px-6 py-14 lg:py-20">
                <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Account</p>
                <h1 className="font-display mt-2 text-4xl font-bold">Reset your password</h1>
                <p className="mt-2 text-sm text-stone-600">Enter the email on your account and we'll send a secure reset link.</p>

                {status && <div className="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{status}</div>}

                <form onSubmit={submit} className="mt-8 grid gap-4 rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-8">
                    <Field label="Email address" error={form.errors.email}><input type="email" autoComplete="email" autoFocus value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                    <button disabled={form.processing} className="mt-2 rounded-full bg-leather-900 px-5 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Sending…' : 'Email reset link'}</button>
                </form>
                <Link href="/account/login" className="mt-6 inline-block text-sm font-bold text-leather-700">Back to sign in</Link>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }> }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}