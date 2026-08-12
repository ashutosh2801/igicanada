import RetailShell from '../../components/RetailShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, ReactElement, cloneElement } from 'react';

type Props = { status?: string };

export default function Login({ status }: Props) {
    const form = useForm({ email: '', password: '', remember: false });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/account/login', { onFinish: () => form.reset('password') });
    }

    return (
        <RetailShell>
            <Head title="Sign in" />
            <main className="mx-auto max-w-md px-6 py-14 lg:py-20">
                <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Account</p>
                <h1 className="font-display mt-2 text-4xl font-bold">Welcome back</h1>
                <p className="mt-2 text-sm text-stone-600">Sign in to view your orders and saved shipping addresses.</p>

                {status && <div className="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{status}</div>}

                <form onSubmit={submit} className="mt-8 grid gap-4 rounded-3xl bg-white p-6 ring-1 ring-leather-900/10 sm:p-8">
                    <Field label="Email address" error={form.errors.email}><input type="email" autoComplete="email" autoFocus value={form.data.email} onChange={e => form.setData('email', e.target.value)} required /></Field>
                    <Field label="Password" error={form.errors.password}><input type="password" autoComplete="current-password" value={form.data.password} onChange={e => form.setData('password', e.target.value)} required /></Field>
                    <div className="flex items-center justify-between gap-4 text-sm">
                        <label className="flex items-center gap-2 font-bold">
                            <input type="checkbox" checked={form.data.remember} onChange={e => form.setData('remember', e.target.checked)} className="size-4 accent-leather-700" />
                            Remember me
                        </label>
                        <Link href="/account/forgot-password" className="font-bold text-leather-700">Forgot password?</Link>
                    </div>
                    <button disabled={form.processing} className="mt-2 rounded-full bg-leather-900 px-5 py-3.5 font-bold text-white transition hover:bg-leather-700 disabled:opacity-50">{form.processing ? 'Signing in…' : 'Sign in'}</button>
                    <p className="mt-3 text-center text-sm font-bold text-stone-500">New here? <Link href="/account/register" className="text-leather-700">Create an account</Link></p>
                </form>
            </main>
        </RetailShell>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactElement<{ className?: string }> }) {
    return <label className="block text-sm font-bold">{label}{cloneElement(children, { className: 'mt-2 w-full rounded-xl border-0 bg-leather-50 px-4 py-3 font-normal ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700' })}{error && <span className="mt-1 block text-xs text-red-700">{error}</span>}</label>;
}