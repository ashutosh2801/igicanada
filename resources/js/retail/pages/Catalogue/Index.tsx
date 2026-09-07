import RetailShell from '../../components/RetailShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Product = { id: number; sku: string | null; name: string; slug: string; image: string | null; price: string | null; compareAtPrice: string | null; inStock: boolean };
type Category = { name: string; slug: string };
type PageLink = { url: string | null; label: string; active: boolean };
type Props = {
    products: { data: Product[]; links: PageLink[]; from: number | null; to: number | null; total: number };
    categories: Category[];
    filters: { q: string; category: string };
};

export default function Catalogue({ products, categories, filters }: Props) {
    const [query, setQuery] = useState(filters.q);
    const selectedCategory = categories.find(category => category.slug === filters.category);
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Home', href: '/' }, { label: selectedCategory?.name || 'Shop' }];
    const canonicalPath = selectedCategory ? `/shop?category=${encodeURIComponent(selectedCategory.slug)}` : '/shop';

    function search(event: FormEvent) {
        event.preventDefault();
        router.get('/shop', { q: query, category: filters.category }, { preserveState: true });
    }

    return (
        <RetailShell>
            <SeoHead title={selectedCategory ? `${selectedCategory.name} leather goods` : 'Shop leather wallets'} description={selectedCategory ? `Shop ${selectedCategory.name.toLowerCase()} from Leather Wallets Canada.` : 'Shop leather wallets and accessories selected for everyday carry in Canada.'} canonicalPath={canonicalPath} noIndex={filters.q !== ''} breadcrumbs={breadcrumbs} />
            <main className="mx-auto max-w-7xl px-6 py-14 lg:px-8 lg:py-20">
                <Breadcrumbs items={breadcrumbs} />
                <p className="text-xs font-bold tracking-[0.22em] text-leather-700 uppercase">The collection</p>
                <div className="mt-3 flex flex-col justify-between gap-6 md:flex-row md:items-end">
                    <div><h1 className="font-display text-5xl font-bold tracking-tight">Shop all</h1><p className="mt-3 text-stone-600">Retail-ready products selected from our shared leather catalogue.</p></div>
                    <form onSubmit={search} className="flex w-full max-w-md rounded-full bg-white p-1 ring-1 ring-leather-900/15">
                        <input value={query} onChange={event => setQuery(event.target.value)} placeholder="Search wallets or SKU" className="min-w-0 flex-1 bg-transparent px-4 py-2 text-sm outline-none" />
                        <button className="rounded-full bg-leather-900 px-5 py-2 text-sm font-bold text-white">Search</button>
                    </form>
                </div>

                <div className="mt-9 flex gap-2 overflow-x-auto pb-2">
                    <Link href="/shop" data={{ q: filters.q }} className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${!filters.category ? 'bg-leather-900 text-white' : 'bg-white ring-1 ring-leather-900/10'}`}>All products</Link>
                    {categories.map(category => <Link key={category.slug} href="/shop" data={{ q: filters.q, category: category.slug }} className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${filters.category === category.slug ? 'bg-leather-900 text-white' : 'bg-white ring-1 ring-leather-900/10'}`}>{category.name}</Link>)}
                </div>

                {products.data.length > 0 ? <div className="mt-10 grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {products.data.map(product => <article key={product.id} className="group">
                        <Link href={'/products/' + product.slug} className="relative block aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-leather-900/10">
                            {!product.inStock && <span className="absolute top-3 left-3 z-10 rounded-full bg-stone-900 px-3 py-1 text-[10px] font-bold text-white uppercase">Sold out</span>}
                            {product.image ? <img src={product.image} alt={product.name} loading="lazy" className="h-full w-full object-contain p-6 transition duration-500 group-hover:scale-105" /> : <div className="grid h-full place-items-center text-xs font-bold tracking-widest text-leather-900/30 uppercase">Product image</div>}
                        </Link>
                        <p className="mt-4 text-xs font-semibold text-stone-500">{product.sku || 'Leather collection'}</p>
                        <h2 className="font-display mt-1 text-xl font-bold"><Link href={'/products/' + product.slug}>{product.name}</Link></h2>
                        <p className="mt-2 font-bold text-leather-700">{product.price ? <span className="inline-flex items-baseline gap-2">From ${product.price} CAD{product.compareAtPrice && <span className="text-xs font-medium text-stone-400 line-through">${product.compareAtPrice}</span>}</span> : 'Price coming soon'}</p>
                    </article>)}
                </div> : <div className="mt-12 rounded-3xl border border-dashed border-leather-900/25 bg-white/50 p-14 text-center"><p className="font-display text-2xl font-bold">No products found</p><p className="mt-2 text-stone-600">Try another search or category.</p></div>}

                {products.links.length > 3 && <nav className="mt-16 flex flex-wrap justify-center gap-2">{products.links.map((link, index) => link.url ? <Link key={index} href={link.url} preserveScroll className={`rounded-full px-4 py-2 text-sm font-bold ${link.active ? 'bg-leather-900 text-white' : 'bg-white ring-1 ring-leather-900/10'}`} dangerouslySetInnerHTML={{ __html: link.label }} /> : <span key={index} className="rounded-full px-4 py-2 text-sm text-stone-400" dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>}
            </main>
        </RetailShell>
    );
}
