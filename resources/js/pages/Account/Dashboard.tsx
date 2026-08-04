import PublicShell from '@/components/PublicShell';
import { Head, Link, router } from '@inertiajs/react';

type Account = {
    name: string;
    email: string;
    accountType: string;
    approvalStatus: string;
    company: string | null;
    priceTier: string | null;
    discountPercentage: string | null;
};

export default function Dashboard({ account }: { account: Account }) {
    return (
        <PublicShell>
            <Head title="My account" />
                <main className="mx-auto min-h-[40rem] max-w-6xl px-6 py-14">
                    <div className="mb-8 flex justify-end gap-5"><Link href="/orders" className="text-sm font-bold">Orders</Link><button onClick={() => router.post('/logout')} className="text-sm font-bold text-red-600">Sign out</button></div>
                    <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">Wholesale account</p>
                    <h1 className="mt-3 text-4xl font-black tracking-tight">Hello, {account.name}</h1>
                    <p className="mt-2 text-stone-600">{account.company || account.email}</p>

                    <section className="mt-10 grid gap-5 md:grid-cols-3">
                        <div className="rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                            <p className="text-sm text-stone-500">Account status</p>
                            <p className="mt-2 text-xl font-bold capitalize">{account.approvalStatus}</p>
                        </div>
                        <div className="rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                            <p className="text-sm text-stone-500">Price level</p>
                            <p className="mt-2 text-xl font-bold">{account.priceTier || 'Standard'}</p>
                        </div>
                        <div className="rounded-2xl bg-white p-6 ring-1 ring-stone-200">
                            <p className="text-sm text-stone-500">Tier discount</p>
                            <p className="mt-2 text-xl font-bold">{account.discountPercentage ? account.discountPercentage + '%' : '—'}</p>
                        </div>
                    </section>

                    <div className="mt-8 rounded-2xl bg-stone-950 p-8 text-white">
                        <h2 className="text-2xl font-bold">Wholesale products</h2>
                        <p className="mt-2 text-stone-400">Browse current products with your protected account pricing and place wholesale order requests.</p>
                        <Link href="/catalogue" className="mt-6 inline-flex rounded-full bg-amber-700 px-5 py-3 text-sm font-bold hover:bg-amber-600">Browse products</Link>
                    </div>
                </main>
        </PublicShell>
    );
}
