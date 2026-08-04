import AuthShell from '@/components/AuthShell';
import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function ResetPassword({ email, token }: { email: string; token: string }) {
    const form = useForm({ email, token, password: '', password_confirmation: '' });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
    }

    return (
        <>
            <Head title="Choose a new password" />
            <AuthShell title="Choose a new password" intro="Use a unique password you do not use on another website.">
                <form onSubmit={submit} className="space-y-5">
                    <label className="block text-sm font-semibold">
                        Email address
                        <input type="email" autoComplete="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                        {form.errors.email && <span className="mt-2 block text-sm text-red-700">{form.errors.email}</span>}
                    </label>
                    <label className="block text-sm font-semibold">
                        New password
                        <input type="password" autoComplete="new-password" autoFocus value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                        {form.errors.password && <span className="mt-2 block text-sm text-red-700">{form.errors.password}</span>}
                    </label>
                    <label className="block text-sm font-semibold">
                        Confirm new password
                        <input type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} className="mt-2 w-full rounded-xl border border-stone-300 bg-white px-4 py-3 font-normal outline-none focus:border-amber-800" />
                    </label>
                    <button disabled={form.processing} className="w-full rounded-xl bg-stone-950 px-5 py-3.5 font-bold text-white disabled:opacity-50">
                        {form.processing ? 'Updating…' : 'Update password'}
                    </button>
                </form>
            </AuthShell>
        </>
    );
}
