import PublicShell from '@/components/PublicShell';
import { Head, router } from '@inertiajs/react';

type Props = {
    account: {
        name: string;
        email: string;
        accountType: string;
        approvalStatus: string;
    };
};

export default function Status({ account }: Props) {
    const pending = account.approvalStatus === 'pending';

    return (
        <PublicShell>
            <Head title="Account status" />
            <main className="mx-auto max-w-3xl px-6 py-16 sm:py-24">
                <p className="text-sm font-black tracking-[0.18em] text-red-600 uppercase">Wholesale account</p>
                <h1 className="mt-3 text-4xl font-black tracking-tight">Account status</h1>
                <div className="mt-8 rounded-2xl border-2 border-black p-6 sm:p-8">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p className="text-xl font-black">{account.name}</p>
                            <p className="mt-1 text-stone-600">{account.email}</p>
                        </div>
                        <span className="rounded-full bg-red-600 px-4 py-2 text-xs font-black tracking-wider text-white uppercase">{account.approvalStatus}</span>
                    </div>
                    <p className="mt-7 leading-7 text-stone-700">
                        {pending
                            ? 'Your wholesale account application is under review. Wholesale prices, cart and checkout will be enabled after an administrator approves the account.'
                            : 'This account does not currently have wholesale access. Please contact IGI Canada if you believe this status should be updated.'}
                    </p>
                    <div className="mt-7 flex flex-wrap gap-3">
                        <a href="/contact" className="rounded-xl bg-black px-5 py-3 text-sm font-bold text-white hover:bg-red-600">Contact IGI Canada</a>
                        <button type="button" onClick={() => router.post('/logout')} className="rounded-xl border-2 border-black px-5 py-3 text-sm font-bold hover:bg-black hover:text-white">Sign out</button>
                    </div>
                </div>
            </main>
        </PublicShell>
    );
}
