import PublicShell from '@/components/PublicShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Result = { type: string; title: string; description: string | null; url: string; image: string | null; accountPrice: string | null; compareAtPrice: string | null };

export default function Search({ query, results, pricingAuthorized }: { query: string; results: Result[]; pricingAuthorized: boolean }) {
    const [value, setValue] = useState(query);
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Home', href: '/' }, { label: 'Search' }];
    function submit(event: FormEvent) {
        event.preventDefault();
        if (value.trim().length >= 2) router.get('/search', { q: value.trim() });
    }

    return (
        <PublicShell>
            <SeoHead title={query ? `Search: ${query}` : 'Search'} canonicalPath="/search" noIndex breadcrumbs={breadcrumbs} />
            <main className="mx-auto max-w-6xl px-6 py-16 lg:px-8 lg:py-24">
                <Breadcrumbs items={breadcrumbs} />
                <p className="text-xs font-black tracking-[0.2em] text-amber-800 uppercase">Global search</p>
                <h1 className="mt-3 text-4xl font-black tracking-tight sm:text-6xl">Find products and information</h1>
                <form onSubmit={submit} className="mt-9 flex max-w-3xl gap-3">
                    <label htmlFor="search-page-input" className="sr-only">Search</label>
                    <input id="search-page-input" value={value} onChange={event => setValue(event.target.value)} placeholder="Product name, SKU, category or page" className="min-w-0 flex-1 rounded-full border border-stone-300 bg-white px-6 py-4 outline-none focus:border-amber-800" autoFocus />
                    <button className="rounded-full bg-stone-950 px-7 py-4 font-bold text-white">Search</button>
                </form>
                {query.length > 0 && query.length < 2 && <p className="mt-5 text-sm text-amber-800">Enter at least 2 characters.</p>}
                {query.length >= 2 && <p className="mt-7 text-sm text-stone-500">{results.length} results for “{query}”</p>}
                <section className="mt-8 grid gap-4 sm:grid-cols-2">
                    {results.map((result, index) => <Link key={result.type + result.url + index} href={result.url} className="flex gap-5 rounded-2xl bg-white p-5 ring-1 ring-stone-900/10 transition hover:shadow-lg">
                        <div className="size-24 shrink-0 overflow-hidden rounded-xl bg-stone-100">{result.image ? <img src={result.image} alt="" className="h-full w-full object-contain p-1" /> : <div className="grid h-full place-items-center text-[10px] font-bold tracking-wider text-stone-400 uppercase">{result.type}</div>}</div>
                        <div className="min-w-0 py-1"><p className="text-xs font-bold tracking-[0.12em] text-amber-800 uppercase">{result.type}</p><h2 className="mt-1 text-lg font-black">{result.title}</h2>{result.description && <p className="mt-2 line-clamp-2 text-sm text-stone-600">{result.description}</p>}{result.type === 'Product' && (pricingAuthorized ? <p className="mt-2 text-base font-black text-black">{result.accountPrice ? <span className="inline-flex items-baseline gap-2">${result.accountPrice} CAD{result.compareAtPrice && <span className="text-base font-medium text-red-600 line-through">${result.compareAtPrice}</span>}</span> : 'Contact for wholesale pricing'}</p> : <p className="mt-2 text-sm font-bold text-red-600">Sign in to view wholesale price</p>)}</div>
                    </Link>)}
                </section>
                {query.length >= 2 && results.length === 0 && <div className="mt-10 rounded-3xl border border-dashed border-stone-300 p-14 text-center text-stone-500">No matching wholesale products, categories or pages were found.</div>}
            </main>
        </PublicShell>
    );
}
