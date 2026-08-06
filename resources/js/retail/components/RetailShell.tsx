import { Head, Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

type SharedProps = {
    seoDefaults: { baseUrl: string };
    flash: { status: string | null };
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
    const { flash, retailStorefront, seoDefaults } = page.props;
    const path = page.url.split('?')[0];
    const privatePage = /^\/(cart|checkout|orders)(\/|$)/.test(path);

    return (
        <div className="min-h-screen bg-white text-ink">
            <Head>
                <meta name="theme-color" content="#d80621" />
                <meta head-key="robots" name="robots" content={privatePage ? 'noindex,follow' : 'index,follow,max-image-preview:large'} />
                <link head-key="canonical" rel="canonical" href={new URL(path, seoDefaults.baseUrl).toString()} />
            </Head>
            {retailStorefront.announcement && <div className="bg-[#d80621] px-6 py-3 text-center text-[14px] font-semibold tracking-[0.22em] text-white uppercase">{retailStorefront.announcement}</div>}
            <header className="sticky top-0 z-40 border-b border-black/10 bg-white/95 backdrop-blur-xl">
                <div className="mx-auto grid max-w-[90rem] grid-cols-[1fr_auto_1fr] items-center px-5 py-4 sm:px-8 sm:py-5">
                    <nav className="hidden items-center gap-7 text-[15px] font-bold tracking-[0.16em] uppercase lg:flex">
                        <Link href="/shop" className="transition hover:text-[#d80621]">Shop</Link>
                        <Link href="/shop?category=wallets" className="transition hover:text-[#d80621]">Wallets</Link>
                        <Link href="/#new-arrivals" className="transition hover:text-[#d80621]">New arrivals</Link>
                    </nav>
                    <span className="lg:hidden" aria-hidden="true" />
                    <Link href="/" className="font-display text-center text-[30px] tracking-[0.08em] text-black uppercase sm:text-[36px]" aria-label={retailStorefront.brandName + ' home'}>{retailStorefront.logoUrl ? <img src={retailStorefront.logoUrl} alt={retailStorefront.logoAlt} className="mx-auto h-9 w-auto sm:h-11" /> : retailStorefront.brandName}</Link>
                    <nav className="ml-auto flex items-center justify-end gap-3 sm:gap-5">
                        <Link href="/shop" aria-label="Search products" className="hidden text-black/70 transition hover:text-black sm:block"><SearchIcon /></Link>
                        <Link href="/cart" className="relative flex items-center gap-2 text-[15px] font-bold tracking-[0.14em] uppercase transition hover:text-[#d80621]">
                            <BagIcon /><span className="hidden sm:inline">Bag</span>
                            {retailStorefront.cartCount > 0 && <span className="absolute -top-2 -right-2 grid size-4 place-items-center rounded-full bg-[#d80621] text-[8px] text-white">{retailStorefront.cartCount}</span>}
                        </Link>
                    </nav>
                </div>
                <nav className="flex justify-start gap-7 overflow-x-auto whitespace-nowrap border-t border-black/5 px-4 py-3 text-[14px] font-bold tracking-[0.16em] uppercase sm:justify-center lg:hidden"><Link href="/shop">Shop</Link><Link href="/shop?category=wallets">Wallets</Link><Link href="/#new-arrivals">New arrivals</Link></nav>
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

function BagIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="size-5" aria-hidden="true"><path d="M5 8.5h14l-1 12H6l-1-12Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>;
}
