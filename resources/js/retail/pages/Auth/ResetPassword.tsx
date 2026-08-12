import RetailShell from '../../components/RetailShell';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement } from 'react';

export default function ResetPassword({ email, token }: { email: string; token: string }) {
    const form = useForm({ email, token, password: '', password_confirmation: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/account/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <RetailShell>
            <Head title="Choose a new password" />
            <main className="mx-auto max-w-md px-6 py-14 lg:py-20">
                <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Account</p>
                <h1 className="font-display mt-2 text-4xl font-bold">Choose a new password</h1>

                <form onSubmit={submit} className="mt-8 grid gap-4 rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-8">
                    <Field label="Email address" error={form.errors.email}><input type="email" autoComplete="email" value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                    <Field label="New password" error={form.errors.password}><input type="password" autoComplete="new-password" autoFocus value={form.data.password} onChange={e => form.setData('password', e.target.value)} required /></Field>
                    <Field label="Confirm new password" error={form.errors.password_confirmation}><input type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={e => form.setData('password_confirmation', e.target.value)} required /></Field>
                    <button disabled={form.processing} className="mt-2 rounded-full bg-leather-900 px-5 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Updating…' : 'Update password'}</button>
                </form>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactElement<{ className?: string }> }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}