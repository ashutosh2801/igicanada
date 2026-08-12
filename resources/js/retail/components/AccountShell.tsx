import RetailShell from '../components/RetailShell';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';

type Auth = { user: { id: number; name: string; email: string } | null };
type SharedProps = { auth: Auth; flash?: { status?: string | null } };

type NavItem = { label: string; href: string };

const signedInNav: NavItem[] = [
    { label: 'Dashboard', href: '/account' },
    { label: 'Profile', href: '/account/profile' },
    { label: 'Addresses', href: '/account/addresses' },
    { label: 'Orders', href: '/account/orders' },
];

export default function AccountShell({ title, children, sidebar }: PropsWithChildren<{ title: string; sidebar?: ReactNode }>) {
    const { auth, flash } = usePage<SharedProps>().props;
    const nav = auth.user ? signedInNav : [{ label: 'Addresses', href: '/account/addresses' }];

    return (
        <RetailShell>
            <Head title={title} />
            <main className="mx-auto max-w-6xl px-6 py-14 lg:px-8 lg:py-20">
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <h1 className="font-display text-4xl font-bold tracking-tight">{title}</h1>
                    {auth.user ? (
                        <button onClick={() => router.post('/account/logout')} className="text-sm font-bold text-red-700">Sign out</button>
                    ) : (
                        <div className="flex gap-4 text-sm font-bold">
                            <Link href="/account/login" className="text-leather-700">Sign in</Link>
                            <Link href="/account/register" className="text-leather-700">Create account</Link>
                        </div>
                    )}
                </div>

                {flash?.status && <div className="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{flash.status}</div>}

                <div className="grid gap-8 lg:grid-cols-[15rem_1fr]">
                    <aside>
                        <nav className="grid gap-1 text-sm">
                            {nav.map(item => (
                                <Link key={item.href} href={item.href} className="rounded-full px-4 py-2 font-bold transition hover:bg-leather-100">{item.label}</Link>
                            ))}
                        </nav>
                        {sidebar}
                    </aside>
                    <div>{children}</div>
                </div>
            </main>
        </RetailShell>
    );
}