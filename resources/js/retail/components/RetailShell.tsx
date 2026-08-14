import { Head, Link, router, usePage } from '@inertiajs/react';
import { PropsWithChildren, useEffect, useRef, useState } from 'react';

type SharedProps = {
    seoDefaults: { baseUrl: string };
    flash: { status: string | null };
    auth: { user: { id: number; name: string; email: string; avatar: string | null } | null };
    retailStorefront: {
        brandName: string;
        logoUrl: string | null;
        logoAlt: string;
        announcement: string | null;
        cartCount: number;
        legalNavigation: { label: string; url: string }[];
        footer: { description: string | null; address: string | null; phone: string | null; email: string | null; copyright: string | null };
    };
};

export default function RetailShell({ children }: PropsWithChildren) {
    const page = usePage<SharedProps>();
    const { flash, retailStorefront, seoDefaults, auth } = page.props;
    const [menuOpen, setMenuOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const path = page.url.split('?')[0];
    const privatePage = /^\/(cart|checkout|orders)(\/|$)/.test(path);

    useEffect(() => {
        function close(event: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                setMenuOpen(false);
            }
        }
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, []);

    return (
        <div className="min-h-screen bg-white text-ink">
            <Head>
                <meta name="theme-color" content="#d80621" />
                <meta head-key="robots" name="robots" content={privatePage ? 'noindex,follow' : 'index,follow,max-image-preview:large'} />
                <link head-key="canonical" rel="canonical" href={new URL(path, seoDefaults.baseUrl).toString()} />
            </Head>
            {retailStorefront.announcement && <div className="bg-[#d80621] px-6 py-3 text-center text-[14px] font-semibold tracking-[0.22em] text-white uppercase">{retailStorefront.announcement}</div>}
            <header className="sticky top-0 z-40 border-b border-black/10 bg-white/95 backdrop-blur-xl">
                <div className="mx-auto flex max-w-[90rem] items-center justify-between gap-4 px-5 py-4 sm:grid sm:grid-cols-[1fr_auto_1fr] sm:gap-0 sm:px-8 sm:py-2">
                    <nav className="hidden items-center gap-7 text-[15px] font-bold tracking-[0.16em] uppercase lg:flex">
                        <Link href="/shop" className="transition hover:text-[#d80621]">Shop</Link>
                        <Link href="/shop?category=wallets" className="transition hover:text-[#d80621]">Wallets</Link>
                        <Link href="/shop?category=bags" className="transition hover:text-[#d80621]">Bags</Link>
                        <Link href="/shop?category=belts" className="transition hover:text-[#d80621]">Belts</Link>
                    </nav>
                    <span className="lg:hidden" aria-hidden="true" />
                    <Link href="/" className="font-display text-left text-[30px] tracking-[0.08em] text-black uppercase sm:text-center sm:text-[36px]" aria-label={retailStorefront.brandName + ' home'}>{retailStorefront.logoUrl ? <img src={retailStorefront.logoUrl} alt={retailStorefront.logoAlt} className="h-9 w-auto sm:mx-auto sm:h-20" /> : retailStorefront.brandName}</Link>
                    <nav className="ml-auto flex items-center justify-end gap-3 sm:gap-5">
                        <Link href="/shop" aria-label="Search products" className="hidden text-black/70 transition hover:text-black sm:block"><SearchIcon /></Link>
                        {auth.user ? (
                            <div ref={menuRef} className="relative">
                                <button onClick={() => setMenuOpen(open => !open)} aria-label="Account menu" className="grid size-10 place-items-center overflow-hidden rounded-full bg-gradient-to-br from-[#d80621] to-black text-sm font-black text-white transition hover:ring-2 hover:ring-[#d80621]/30">{auth.user.avatar ? <img src={auth.user.avatar} alt={auth.user.name} className="size-full object-cover" /> : initials(auth.user.name)}</button>
                                {menuOpen && (
                                    <div className="absolute right-0 top-12 w-56 rounded-2xl bg-white p-2 shadow-2xl ring-1 ring-black/10">
                                        <div className="border-b border-black/5 px-3 py-2.5">
                                            <p className="truncate text-sm font-bold">{auth.user.name}</p>
                                            <p className="truncate text-xs text-black/50">{auth.user.email}</p>
                                        </div>
                                        <Link href="/account" onClick={() => setMenuOpen(false)} className="block rounded-xl px-3 py-2.5 text-sm font-bold transition hover:bg-black/5">My account</Link>
                                        <Link href="/account/orders" onClick={() => setMenuOpen(false)} className="block rounded-xl px-3 py-2.5 text-sm font-bold transition hover:bg-black/5">Orders</Link>
                                        <Link href="/account/addresses" onClick={() => setMenuOpen(false)} className="block rounded-xl px-3 py-2.5 text-sm font-bold transition hover:bg-black/5">Addresses</Link>
                                        <button onClick={() => router.post('/account/logout')} className="mt-1 block w-full rounded-xl px-3 py-2.5 text-left text-sm font-bold text-[#d80621] transition hover:bg-[#d80621]/5">Sign out</button>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <Link href="/account/login" aria-label="Sign in" className="text-black/70 transition hover:text-black"><UserIcon /></Link>
                        )}
                        <Link href="/cart" className="relative flex items-center gap-2 text-[15px] font-bold tracking-[0.14em] uppercase transition hover:text-[#d80621]">
                            <BagIcon /><span className="hidden sm:inline">Bag</span>
                            {retailStorefront.cartCount > 0 && <span className="absolute -top-2 -right-2 grid size-4 place-items-center rounded-full bg-[#d80621] text-[8px] text-white">{retailStorefront.cartCount}</span>}
                        </Link>
                    </nav>
                </div>
                <nav className="flex justify-start gap-7 overflow-x-auto whitespace-nowrap border-t border-black/5 px-4 py-3 text-[14px] font-bold tracking-[0.16em] uppercase sm:justify-center lg:hidden"><Link href="/shop">Shop</Link><Link href="/shop?category=wallets">Wallets</Link><Link href="/shop?category=bags">Bags</Link><Link href="/shop?category=belts">Belts</Link></nav>
            </header>
            {flash.status && <div className="border-b border-green-900/10 bg-green-50 px-6 py-3 text-center text-sm font-bold text-green-900">{flash.status}</div>}
            {children}
            <footer className="border-t-4 border-[#d80621] bg-[#f5f5f5] px-6 py-14 text-black sm:py-20">
                <div className="mx-auto grid max-w-7xl gap-12 md:grid-cols-[1.4fr_1fr_1fr]">
                    <div><p className="font-display text-3xl tracking-wide uppercase">{retailStorefront.brandName}</p>{retailStorefront.footer.description && <p className="mt-4 max-w-sm text-sm leading-6 text-black/55">{retailStorefront.footer.description}</p>}{retailStorefront.footer.address && <p className="mt-4 text-xs text-black/45">{retailStorefront.footer.address}</p>}</div>
                    <div><p className="text-[10px] font-bold tracking-[0.2em] uppercase">Customer care</p><div className="mt-5 flex flex-col gap-3 text-xs text-black/60">{retailStorefront.legalNavigation.map(item => <Link key={item.url} href={item.url} className="hover:text-black">{item.label}</Link>)}</div></div>
                    <div><p className="text-[10px] font-bold tracking-[0.2em] uppercase">Contact</p><div className="mt-5 flex flex-col gap-3 text-xs text-black/60">{retailStorefront.footer.email && <a href={`mailto:${retailStorefront.footer.email}`} className="hover:text-black">{retailStorefront.footer.email}</a>}{retailStorefront.footer.phone && <a href={`tel:${retailStorefront.footer.phone}`} className="hover:text-black">{retailStorefront.footer.phone}</a>}<Link href="/shop" className="hover:text-black">Shop all</Link></div></div>
                </div>
                <div className="mx-auto mt-14 flex max-w-7xl flex-col justify-between gap-3 border-t border-black/10 pt-6 text-[10px] tracking-wide text-black/40 sm:flex-row"><p>© {new Date().getFullYear()} {retailStorefront.footer.copyright || retailStorefront.brandName}</p><p>Canada · CAD</p></div>
            </footer>
        </div>
    );
}

function SearchIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="size-5" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path d="m16 16 4 4" /></svg>;
}

function UserIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="size-6" aria-hidden="true"><circle cx="12" cy="8" r="3.5" /><path d="M5 20c1.5-3 4-4.5 7-4.5s5.5 1.5 7 4.5" /></svg>;
}

function BagIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="size-5" aria-hidden="true"><path d="M5 8.5h14l-1 12H6l-1-12Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>;
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'U';
}
