import AuthShell from '@/components/AuthShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const form = useForm({ email: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/forgot-password');
    }

    return (
        <>
            <Head title="Reset password" />
            <AuthShell title="Reset your password" intro={'Enter the email used on your existing IGI Canada account. We\u2019ll send a secure reset link.'} image="/login-left.jpg">
                {status && <div className="mb-5 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">{status}</div>}
                <form onSubmit={submit} className="space-y-5">
                    <label className="block text-sm font-semibold">
                        Email address
                        <input type="email" autoComplete="email" autoFocus value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                        {form.errors.email && <span className="mt-2 block text-sm text-red-700">{form.errors.email}</span>}
                    </label>
                    <button disabled={form.processing} className="w-full rounded-xl bg-stone-950 px-5 py-3.5 font-bold text-white disabled:opacity-50">
                        {form.processing ? 'Sending…' : 'Email reset link'}
                    </button>
                </form>
                <Link href="/login" className="mt-6 inline-block text-sm font-semibold text-amber-800 hover:underline">Back to sign in</Link>
            </AuthShell>
        </>
    );
}
