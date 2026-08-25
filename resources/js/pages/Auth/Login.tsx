import AuthShell from '@/components/AuthShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Props = { status?: string };

export default function Login({ status }: Props) {
    const form = useForm({ email: '', password: '', remember: false });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }

    return (
        <>
            <Head title="Wholesale account sign in" />
            <AuthShell title="Welcome back" intro="Sign in to manage your wholesale account and view approved pricing." image="/login-left.jpg">
                {status && <div className="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{status}</div>}
                <form onSubmit={submit} className="space-y-5">
                    <label className="block text-sm font-semibold">
                        Email address
                        <input type="email" autoComplete="email" autoFocus value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                        {form.errors.email && <span className="mt-2 block text-sm text-red-700">{form.errors.email}</span>}
                    </label>
                    <label className="block text-sm font-semibold">
                        Password
                        <input type="password" autoComplete="current-password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                        {form.errors.password && <span className="mt-2 block text-sm text-red-700">{form.errors.password}</span>}
                    </label>
                    <div className="flex items-center justify-between gap-4 text-sm">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                            Remember me
                        </label>
                        <Link href="/forgot-password" className="font-semibold text-amber-800 hover:underline">Forgot password?</Link>
                    </div>
                    <button disabled={form.processing} className="w-full rounded-xl bg-stone-950 px-5 py-3.5 font-bold text-white disabled:opacity-50">
                        {form.processing ? 'Signing in…' : 'Sign in'}
                    </button>
                </form>
                <p className="mt-6 text-sm leading-6 text-stone-600">Migrated from the previous website? Use <Link href="/forgot-password" className="font-semibold text-amber-800 hover:underline">Forgot password</Link> before your first sign in.</p>
                <p className="mt-3 text-sm leading-6 text-stone-600">New to wholesale? <Link href="/wholesale/apply" className="font-semibold text-amber-800 hover:underline">Create an account</Link> to apply for a wholesale account.</p>
            </AuthShell>
        </>
    );
}
