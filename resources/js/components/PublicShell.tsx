import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, PropsWithChildren, useState } from 'react';

type NavigationItem = { label: string; url: string; opens_new_tab: boolean };
type CategoryItem = { name: string; slug: string; url: string; children: CategoryItem[] };
type SharedProps = {
    seoDefaults: { baseUrl: string };
    storefront: {
        brandName: string;
        logoUrl: string | null;
        logoAlt: string;
        announcement: string | null;
        showCategoryMenu: boolean;
        categoryMenuLabel: string;
        categoryNavigation: CategoryItem[];
        headerNavigation: NavigationItem[];
        footerNavigation: NavigationItem[];
        footer: { description: string | null; address: string | null; phone: string | null; email: string | null; copyright: string | null };
        accountNavigation: { label: string; url: string };
        cartNavigation: { url: string | null; available: boolean };
        cartCount: number;
        seo: {
            title: string;
            description: string | null;
            faviconUrl: string | null;
            ogTitle: string | null;
            ogDescription: string | null;
            ogImageUrl: string | null;
            twitterCard: string;
            twitterTitle: string | null;
            twitterDescription: string | null;
            twitterImageUrl: string | null;
        };
    };
};

export default function PublicShell({ children }: PropsWithChildren) {
    const page = usePage<SharedProps>();
    const { storefront, seoDefaults } = page.props;
    const path = page.url.split('?')[0];
    const privatePage = /^\/(account|cart|checkout|orders|login|forgot-password|reset-password|wholesale\/application-received)(\/|$)/.test(path);
    const [query, setQuery] = useState('');

    function search(event: FormEvent) {
        event.preventDefault();
        if (query.trim().length >= 2) router.get('/search', { q: query.trim() });
    }

    return (
        <div className="min-h-screen bg-white text-black">
            <Head>
                <meta head-key="robots" name="robots" content={privatePage ? 'noindex,follow' : 'index,follow,max-image-preview:large'} />
                <link head-key="canonical" rel="canonical" href={new URL(path, seoDefaults.baseUrl).toString()} />
                {storefront.seo.description && <meta head-key="description" name="description" content={storefront.seo.description} />}
                {storefront.seo.faviconUrl && <link head-key="favicon" rel="icon" href={storefront.seo.faviconUrl} />}
                <meta head-key="og-type" property="og:type" content="website" />
                <meta head-key="og-title" property="og:title" content={storefront.seo.ogTitle || storefront.seo.title} />
                {storefront.seo.ogDescription && <meta head-key="og-description" property="og:description" content={storefront.seo.ogDescription} />}
                {storefront.seo.ogImageUrl && <meta head-key="og-image" property="og:image" content={storefront.seo.ogImageUrl} />}
                <meta head-key="twitter-card" name="twitter:card" content={storefront.seo.twitterCard} />
                <meta head-key="twitter-title" name="twitter:title" content={storefront.seo.twitterTitle || storefront.seo.ogTitle || storefront.seo.title} />
                {storefront.seo.twitterDescription && <meta head-key="twitter-description" name="twitter:description" content={storefront.seo.twitterDescription} />}
                {storefront.seo.twitterImageUrl && <meta head-key="twitter-image" name="twitter:image" content={storefront.seo.twitterImageUrl} />}
            </Head>
            {storefront.announcement && <div className="bg-red-600 px-6 py-2.5 text-center text-[11px] font-bold tracking-[0.2em] text-white uppercase">{storefront.announcement}</div>}
            <header className="sticky top-0 z-40 bg-white shadow-sm">
                <div className="mx-auto flex max-w-[90rem] items-center gap-4 px-5 py-4 sm:gap-6 sm:px-8">
                    <Link href="/" className="flex shrink-0 items-center" aria-label={storefront.brandName + ' homepage'}>
                        {storefront.logoUrl ? <img src={storefront.logoUrl} alt={storefront.logoAlt} className="h-10 w-auto max-w-40 object-contain sm:h-12 sm:max-w-48" /> : <span className="text-xl font-black tracking-[-0.04em] sm:text-2xl">IGI <span className="text-red-600">CANADA</span></span>}
                    </Link>
                    <form onSubmit={search} role="search" className="ml-auto hidden max-w-2xl flex-1 md:flex">
                        <label htmlFor="global-search" className="sr-only">Search products, categories and pages</label>
                        <div className="flex w-full items-center rounded-full border-2 border-black bg-white px-4 focus-within:border-red-600">
                            <SearchIcon />
                            <input id="global-search" value={query} onChange={event => setQuery(event.target.value)} placeholder="Search products, SKU, categories and pages" className="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm outline-none" />
                            <button className="text-xs font-black text-red-600 uppercase">Search</button>
                        </div>
                    </form>
                    <div className="flex shrink-0 items-center gap-1 sm:gap-2">
                        <Link href={storefront.accountNavigation.url} className="hidden rounded-full px-4 py-2.5 text-sm font-bold transition hover:bg-red-600 hover:text-white sm:inline-flex">{storefront.accountNavigation.label}</Link>
                        {storefront.cartNavigation.url ? (
                            <Link href={storefront.cartNavigation.url} className="relative grid size-11 place-items-center rounded-full bg-black text-white" aria-label={storefront.cartNavigation.available ? `Cart with ${storefront.cartCount} items` : 'Sign in to use the wholesale cart'}>
                                <CartIcon />
                                {storefront.cartCount > 0 && <span className="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-red-600 px-1 text-[10px] font-black leading-5 text-white">{storefront.cartCount}</span>}
                            </Link>
                        ) : (
                            <span className="grid size-11 cursor-not-allowed place-items-center rounded-full bg-black/35 text-white" aria-label="Wholesale cart is available to approved wholesale accounts" title="Wholesale cart is available to approved wholesale accounts">
                                <CartIcon />
                            </span>
                        )}
                    </div>
                </div>

                <nav className="relative bg-black text-white" aria-label="Store navigation">
                    <div className="mx-auto hidden max-w-[90rem] items-stretch px-8 lg:flex">
                        {storefront.showCategoryMenu && (
                            <div className="group static">
                                <button className="flex h-full min-w-48 items-center justify-between gap-4 bg-red-600 px-5 py-4 text-sm font-black uppercase">
                                    <span>{storefront.categoryMenuLabel}</span><ChevronDown />
                                </button>
                                <div className="invisible absolute inset-x-0 top-full z-50 max-h-[72vh] overflow-y-auto border-b-4 border-red-600 bg-white text-black opacity-0 shadow-2xl transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                                    <div className="mx-auto max-w-[90rem] columns-2 gap-6 px-6 py-6 md:columns-3 lg:columns-4 xl:columns-5">
                                        {storefront.categoryNavigation.map(category => <CategoryColumn key={category.slug} category={category} />)}
                                    </div>
                                </div>
                            </div>
                        )}
                        <div className="flex items-center gap-1 overflow-x-auto px-4">
                            {storefront.headerNavigation.map(item => <a key={item.label + item.url} href={item.url} target={item.opens_new_tab ? '_blank' : undefined} rel={item.opens_new_tab ? 'noopener noreferrer' : undefined} className="shrink-0 px-5 py-4 text-sm font-bold transition hover:bg-red-600">{item.label}</a>)}
                        </div>
                    </div>

                    <div className="mx-auto max-w-[90rem] px-5 py-3 sm:px-8 lg:hidden">
                        <div className="flex items-center gap-5 overflow-x-auto text-sm font-bold">
                            {storefront.showCategoryMenu && <details className="group shrink-0"><summary className="flex cursor-pointer list-none items-center gap-2 text-red-500"><span>{storefront.categoryMenuLabel}</span><ChevronDown /></summary><div className="fixed inset-x-0 z-50 mt-3 max-h-[68vh] overflow-y-auto border-b-4 border-red-600 bg-white p-5 text-black shadow-2xl"><div className="grid gap-2">{storefront.categoryNavigation.map(category => <MobileCategory key={category.slug} category={category} />)}</div></div></details>}
                            {storefront.headerNavigation.map(item => <a key={item.label + item.url} href={item.url} className="shrink-0">{item.label}</a>)}
                            <Link href={storefront.accountNavigation.url} className="shrink-0 sm:hidden">{storefront.accountNavigation.label}</Link>
                        </div>
                        <form onSubmit={search} role="search" className="mt-3 flex md:hidden">
                            <label htmlFor="mobile-global-search" className="sr-only">Search products, categories and pages</label>
                            <div className="flex w-full items-center rounded-full bg-white px-4 text-black">
                                <SearchIcon />
                                <input id="mobile-global-search" value={query} onChange={event => setQuery(event.target.value)} placeholder="Search everything" className="min-w-0 flex-1 bg-transparent px-3 py-2.5 text-sm outline-none" />
                            </div>
                        </form>
                    </div>
                </nav>
            </header>
            {children}
            <footer className="bg-black text-white">
                <div className="h-2 bg-red-600" />
                <div className="mx-auto grid max-w-[90rem] gap-12 px-6 py-16 md:grid-cols-[1.2fr_0.8fr_1fr] lg:px-8">
                    <div>
                        {storefront.logoUrl ? <img src={storefront.logoUrl} alt={storefront.logoAlt} className="h-12 w-auto max-w-48 brightness-0 invert" /> : <p className="text-2xl font-black text-white">IGI <span className="text-red-600">CANADA</span></p>}
                        {storefront.footer.description && <p className="mt-5 max-w-md text-sm leading-7 text-white/70">{storefront.footer.description}</p>}
                    </div>
                    <div className="text-sm">
                        <p className="font-bold tracking-[0.12em] text-red-500 uppercase">Explore</p>
                        <div className="mt-5 grid gap-3 text-white/70">
                            {storefront.footerNavigation.map(item => <a key={item.label + item.url} href={item.url} target={item.opens_new_tab ? '_blank' : undefined} rel={item.opens_new_tab ? 'noopener noreferrer' : undefined} className="hover:text-white">{item.label}</a>)}
                        </div>
                    </div>
                    <div className="text-sm leading-7">
                        <p className="font-bold tracking-[0.12em] text-red-500 uppercase">Contact</p>
                        {storefront.footer.address && <p className="mt-5 text-white/70">{storefront.footer.address}</p>}
                        {storefront.footer.phone && <a href={'tel:' + storefront.footer.phone.replace(/[^+\d]/g, '')} className="mt-3 block hover:text-red-500">{storefront.footer.phone}</a>}
                        {storefront.footer.email && <a href={'mailto:' + storefront.footer.email} className="block hover:text-red-500">{storefront.footer.email}</a>}
                    </div>
                </div>
                <div className="border-t border-white/10 px-6 py-5 text-center text-xs text-white/50">© {new Date().getFullYear()} {storefront.footer.copyright || storefront.brandName} · <a href="https://github.com/dr5hn/countries-states-cities-database" target="_blank" rel="noopener noreferrer" className="hover:text-white">Location data by Countries States Cities Database</a></div>
            </footer>
        </div>
    );
}

function CategoryColumn({ category }: { category: CategoryItem }) {
    return <section className="mb-5 inline-block w-full break-inside-avoid"><a href={category.url} className="inline-block border-b-2 border-red-600 pb-1.5 text-base font-black">{category.name}</a>{category.children.length > 0 && <ul className="mt-3 grid gap-2.5">{category.children.map(child => <li key={child.slug}><a href={child.url} className="font-bold hover:text-red-600">{child.name}</a>{child.children.length > 0 && <ul className="mt-1.5 grid gap-1 border-l-2 border-black/10 pl-3 text-sm">{child.children.map(grandchild => <li key={grandchild.slug}><a href={grandchild.url} className="hover:text-red-600">{grandchild.name}</a></li>)}</ul>}</li>)}</ul>}</section>;
}

function MobileCategory({ category }: { category: CategoryItem }) {
    if (category.children.length === 0) return <a href={category.url} className="border-b border-black/10 py-3 font-bold">{category.name}</a>;
    return <details><summary className="cursor-pointer list-none border-b border-black/10 py-3 font-black">{category.name}</summary><div className="grid border-b border-black/10 bg-black/5 px-4 py-2">{category.children.map(child => <div key={child.slug}><a href={child.url} className="block py-2 font-bold">{child.name}</a>{child.children.map(grandchild => <a key={grandchild.slug} href={grandchild.url} className="block border-l-2 border-red-600 py-1.5 pl-3 text-sm">{grandchild.name}</a>)}</div>)}</div></details>;
}

function SearchIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" className="size-4" aria-hidden="true"><circle cx="11" cy="11" r="7" strokeWidth="2" /><path d="m20 20-3.5-3.5" strokeWidth="2" strokeLinecap="round" /></svg>;
}

function CartIcon() {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" className="size-5" aria-hidden="true"><path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 7H6" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" /><circle cx="10" cy="20" r="1.2" fill="currentColor" stroke="none" /><circle cx="18" cy="20" r="1.2" fill="currentColor" stroke="none" /></svg>;
}

function ChevronDown() {
    return <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" className="size-4 transition group-open:rotate-180" aria-hidden="true"><path d="m5 7.5 5 5 5-5" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" /></svg>;
}
