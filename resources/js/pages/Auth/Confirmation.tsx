import AuthShell from '@/components/AuthShell';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Props = {
    email: string | null;
    status: string | null;
};

export default function Confirmation({ email, status }: Props) {
    const form = useForm({ email: email || '' });

    function resend(event: FormEvent) {
        event.preventDefault();
        form.post('/wholesale/resend-verification', { preserveScroll: true });
    }

    return (
        <>
            <Head title="Wholesale application received" />
            <AuthShell title="Check your email" intro="Your wholesale account application has been saved.">
                <div className="rounded-2xl border-2 border-red-600 bg-red-50 p-6">
                    <p className="text-sm font-black tracking-wider text-red-700 uppercase">Application received</p>
                    <p className="mt-3 leading-7 text-black/70">{status || 'Use the verification link in your email to confirm your business email address.'}</p>
                    <ol className="mt-5 grid gap-3 text-sm font-semibold text-black/70">
                        <li><span className="mr-2 text-red-600">1.</span>Verify your email address.</li>
                        <li><span className="mr-2 text-red-600">2.</span>IGI Canada reviews your wholesale application.</li>
                        <li><span className="mr-2 text-red-600">3.</span>After approval, sign in to access pricing and ordering.</li>
                    </ol>
                </div>

                <form onSubmit={resend} className="mt-7">
                    <label className="block">
                        <span className="text-sm font-semibold">Business email</span>
                        <input type="email" required value={form.data.email} onChange={event => form.setData('email', event.target.value)} className="mt-2 w-full rounded-xl border border-black/20 px-4 py-3 outline-none focus:border-red-600" />
                        {form.errors.email && <span className="mt-1 block text-sm text-red-700">{form.errors.email}</span>}
                    </label>
                    <button disabled={form.processing} className="mt-4 rounded-xl bg-black px-5 py-3 text-sm font-bold text-white hover:bg-red-600 disabled:opacity-50">{form.processing ? 'Sending…' : 'Resend verification email'}</button>
                </form>

                <p className="mt-6 text-sm text-black/60">Already verified and approved? <Link href="/login" className="font-bold text-red-600 hover:underline">Wholesale sign in</Link></p>
            </AuthShell>
        </>
    );
}
