import PublicShell from '@/components/PublicShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Product = {
    id: number;
    sku: string | null;
    name: string;
    slug: string;
    image: string | null;
    variants: number;
    inStock: boolean;
    wholesalePrice: string | null;
    accountPrice: string | null;
    compareAtPrice: string | null;
};

type PageLink = { url: string | null; label: string; active: boolean };

type Props = {
    products: { data: Product[]; links: PageLink[]; total: number };
    categories: { name: string; slug: string }[];
    filters: { q: string; category: string };
    pricing: { authorized: boolean; tier: string | null };
    clearance?: boolean;
};

export default function Catalogue({ products, categories, filters, pricing, clearance = false }: Props) {
    const [query, setQuery] = useState(filters.q);
    const selectedCategory = categories.find(category => category.slug === filters.category);
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Home', href: '/' }, { label: clearance ? 'Clearance' : (selectedCategory?.name || 'Wholesale catalogue') }];
    const canonicalPath = clearance ? '/clearance' : (selectedCategory ? `/catalogue?category=${encodeURIComponent(selectedCategory.slug)}` : '/catalogue');

    function search(event: FormEvent) {
        event.preventDefault();
        router.get('/catalogue', { q: query, category: filters.category }, { preserveState: true });
    }

    return (
        <PublicShell>
            <SeoHead title={clearance ? 'Clearance wholesale products' : (selectedCategory ? `${selectedCategory.name} wholesale` : 'Wholesale leather goods catalogue')} description={clearance ? 'Browse discounted wholesale products with clearance prices from IGI Canada.' : (selectedCategory ? `Browse wholesale ${selectedCategory.name.toLowerCase()} from IGI Canada.` : 'Browse IGI Canada wholesale leather goods, wallets, bags, belts and accessories.')} canonicalPath={canonicalPath} noIndex={filters.q !== ''} breadcrumbs={breadcrumbs} />
                <main className="mx-auto min-h-[40rem] max-w-7xl px-6 py-12 lg:px-8">
                    <Breadcrumbs items={breadcrumbs} />
                    <div className="flex flex-col justify-between gap-6 border-b border-stone-200 pb-8 md:flex-row md:items-end">
                        <div>
                            <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">{clearance ? 'Clearance' : 'Wholesale products'}</p>
                            <h1 className="mt-2 text-4xl font-black tracking-tight">{clearance ? 'Clearance deals' : 'Products built to sell through'}</h1>
                            <p className="mt-2 text-stone-600">{clearance ? `Browse ${products.total} discounted wholesale products.` : `Browse all ${products.total} active wholesale products.`}</p>
                        </div>
                        <form onSubmit={search} className="flex w-full max-w-md gap-2">
                            <label htmlFor="catalogue-search" className="sr-only">Search products</label>
                            <input id="catalogue-search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search by product or SKU" className="min-w-0 flex-1 rounded-full border border-stone-300 bg-white px-5 py-3 outline-none focus:border-amber-800" />
                            <button className="rounded-full bg-stone-950 px-5 py-3 font-bold text-white">Search</button>
                        </form>
                    </div>

                    <div className="mt-8 flex gap-2 overflow-x-auto pb-2">
                        <Link href="/catalogue" className={`shrink-0 rounded-full px-4 py-2 text-sm font-semibold ${!filters.category ? 'bg-stone-950 text-white' : 'bg-white ring-1 ring-stone-200'}`}>All</Link>
                        {categories.map((category) => (
                            <Link key={category.slug} href="/catalogue" data={{ category: category.slug, q: filters.q || undefined }} preserveState className={`shrink-0 rounded-full px-4 py-2 text-sm font-semibold ${filters.category === category.slug ? 'bg-stone-950 text-white' : 'bg-white ring-1 ring-stone-200'}`}>{category.name}</Link>
                        ))}
                    </div>

                    <section className="mt-8 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.data.map((product) => (
                            <article key={product.id} className="group">
                                <Link href={'/catalogue/' + product.slug} className="block aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">
                                    {product.image ? <img src={product.image} alt={product.name} loading="lazy" className="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-105" /> : <div className="grid h-full place-items-center text-sm text-stone-400">Image unavailable</div>}
                                </Link>
                                <div className="mt-4 flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-xs font-semibold text-stone-500">{product.sku || 'Uncoded'} · {product.variants} variants</p>
                                        <h2 className="mt-1 font-bold leading-snug"><Link href={'/catalogue/' + product.slug}>{product.name}</Link></h2>
                                    </div>
                                    <span className={`mt-1 size-2 shrink-0 rounded-full ${product.inStock ? 'bg-emerald-500' : 'bg-stone-300'}`} title={product.inStock ? 'In stock' : 'Out of stock'} />
                                </div>
                                {pricing.authorized ? (
                                    product.accountPrice ? <div className="mt-3 flex items-baseline gap-2"><p className="text-lg font-black text-black">From ${product.accountPrice} CAD</p>{product.compareAtPrice && <p className="text-base text-red-600 line-through">${product.compareAtPrice}</p>}{!product.compareAtPrice && product.accountPrice !== product.wholesalePrice && <p className="text-base text-red-600 line-through">${product.wholesalePrice}</p>}</div> : <p className="mt-3 text-sm font-semibold text-black/50">Contact for wholesale pricing</p>
                                ) : <p className="mt-3 text-sm font-semibold text-red-600">Sign in to view wholesale price</p>}
                            </article>
                        ))}
                    </section>

                    {products.data.length === 0 && <div className="py-24 text-center text-stone-500">No products matched your search.</div>}

                    <nav className="mt-14 flex flex-wrap justify-center gap-2" aria-label="Products pagination">
                        {products.links.map((link, index) => link.url ? <Link key={index} href={link.url} preserveScroll className={`rounded-lg px-3 py-2 text-sm ${link.active ? 'bg-stone-950 text-white' : 'bg-white ring-1 ring-stone-200'}`} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={index} className="rounded-lg px-3 py-2 text-sm text-stone-300" dangerouslySetInnerHTML={{ __html: link.label }} />)}
                    </nav>
                </main>
        </PublicShell>
    );
}
