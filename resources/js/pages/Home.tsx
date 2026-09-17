import PublicShell from '@/components/PublicShell';
import SeoHead from '@/components/SeoHead';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Homepage = {
    heroEyebrow: string | null;
    heroTitle: string;
    heroDescription: string | null;
    heroImages: string[];
    heroSliderInterval: number;
    heroPrimaryLabel: string | null;
    heroPrimaryUrl: string | null;
    heroSecondaryLabel: string | null;
    heroSecondaryUrl: string | null;
    catalogueEyebrow: string | null;
    catalogueTitle: string;
    catalogueDescription: string | null;
    showNewArrivals: boolean;
    newArrivalsEyebrow: string | null;
    newArrivalsTitle: string;
    newArrivalsDescription: string | null;
};
type Category = { name: string; slug: string; description: string; image: string | null; products: number };
type NewArrival = { id: number; sku: string | null; name: string; slug: string; image: string | null; variants: number; inStock: boolean; wholesalePrice: string | null; accountPrice: string | null; compareAtPrice: string | null };
type Props = { homepage: Homepage; categories: Category[]; newArrivals: NewArrival[]; catalogue: { categories: number; products: number; variants: number }; pricing: { authorized: boolean; tier: string | null } };

export default function Home({ homepage, categories, newArrivals, catalogue, pricing }: Props) {
    return (
        <PublicShell>
            <SeoHead title="Wholesale leather goods" description={homepage.heroDescription || 'IGI Canada wholesale leather goods and products.'} canonicalPath="/" schemas={[{
                '@context': 'https://schema.org',
                '@type': 'WebSite',
                name: 'IGI Canada',
                url: 'https://igicanada.ca/',
            }, {
                '@context': 'https://schema.org',
                '@type': 'Organization',
                name: 'IGI Canada',
                url: 'https://igicanada.ca/',
            }]} />
            <main>
                <section className="overflow-hidden border-b border-stone-900/10">
                    <div className="mx-auto grid min-h-[20rem] max-w-[90rem] lg:grid-cols-[1.02fr_0.98fr]">
                        <div className="flex items-center px-6 py-10 sm:px-10 lg:px-14 lg:py-12 xl:px-20">
                            <div className="max-w-3xl">
                                {homepage.heroEyebrow && <p className="text-[10px] font-black tracking-[0.22em] text-amber-800 uppercase">{homepage.heroEyebrow}</p>}
                                <h1 className="mt-4 text-4xl leading-[0.94] font-black tracking-[-0.055em] text-balance sm:text-[3.375rem] xl:text-[4.125rem]">{homepage.heroTitle}</h1>
                                {homepage.heroDescription && <p className="mt-5 max-w-2xl text-base leading-7 text-stone-600 sm:text-lg">{homepage.heroDescription}</p>}
                                <div className="mt-7 flex flex-wrap gap-3">
                                    {homepage.heroPrimaryLabel && homepage.heroPrimaryUrl && <Link href={homepage.heroPrimaryUrl} className="rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-black">{homepage.heroPrimaryLabel}</Link>}
                                    {homepage.heroSecondaryLabel && homepage.heroSecondaryUrl && <Link href={homepage.heroSecondaryUrl} className="rounded-full bg-white px-5 py-2.5 text-sm font-bold ring-1 ring-stone-900/15 transition hover:ring-stone-900/40">{homepage.heroSecondaryLabel}</Link>}
                                </div>
                            </div>
                        </div>
                        <HeroSlider images={homepage.heroImages} interval={homepage.heroSliderInterval} />
                    </div>
                </section>

                <section className="border-b border-stone-900/10 bg-white">
                    <dl className="mx-auto grid max-w-[90rem] grid-cols-3 divide-x divide-stone-900/10 px-4 py-7 sm:px-8">
                        {Object.entries(catalogue).map(([label, value]) => <div key={label} className="px-3 text-center sm:px-8"><dd className="text-2xl font-black sm:text-4xl">{value}</dd><dt className="mt-1 text-[10px] font-bold tracking-[0.16em] text-stone-500 uppercase sm:text-xs">{label}</dt></div>)}
                    </dl>
                </section>

                <section id="catalogue" className="mx-auto max-w-[90rem] px-6 py-20 sm:px-8 lg:py-28">
                    <div className="flex flex-col justify-between gap-7 md:flex-row md:items-end">
                        <div className="max-w-3xl">
                            {homepage.catalogueEyebrow && <p className="text-xs font-black tracking-[0.22em] text-amber-800 uppercase">{homepage.catalogueEyebrow}</p>}
                            <h2 className="mt-4 text-4xl font-black tracking-[-0.04em] sm:text-6xl">{homepage.catalogueTitle}</h2>
                            {homepage.catalogueDescription && <p className="mt-5 text-lg leading-8 text-stone-600">{homepage.catalogueDescription}</p>}
                        </div>
                        <Link href="/catalogue" className="shrink-0 text-sm font-black text-amber-800">View all products <span aria-hidden="true">→</span></Link>
                    </div>
                    <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {categories.map(category => (
                            <Link key={category.slug} href="/catalogue" data={{ category: category.slug }} className="group overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-stone-900/10 transition hover:-translate-y-1 hover:shadow-xl">
                                <div className="aspect-[4/3] overflow-hidden bg-stone-100">
                                    {category.image ? <img src={category.image} alt={category.name} loading="lazy" className="h-full w-full object-cover transition duration-500 group-hover:scale-105" /> : <div className="grid h-full place-items-center text-xs font-bold tracking-widest text-stone-400 uppercase">Category image</div>}
                                </div>
                                <div className="p-6">
                                    <p className="text-xs font-bold tracking-[0.15em] text-amber-800 uppercase">{category.products} products</p>
                                    <h3 className="mt-2 text-xl font-black tracking-tight">{category.name}</h3>
                                    {category.description && <p className="mt-2 line-clamp-2 text-sm leading-6 text-stone-600">{category.description}</p>}
                                    <p className="mt-5 text-sm font-bold">Browse collection <span className="transition group-hover:translate-x-1">→</span></p>
                                </div>
                            </Link>
                        ))}
                    </div>
                    {categories.length === 0 && <div className="mt-12 rounded-3xl border border-dashed border-stone-300 p-12 text-center text-stone-500">Select featured categories in the Homepage admin editor.</div>}
                </section>

                {homepage.showNewArrivals && (
                    <section className="border-t border-black/10 bg-white px-6 py-20 sm:px-8 lg:py-28">
                        <div className="mx-auto max-w-[90rem]">
                            <div className="flex flex-col justify-between gap-7 md:flex-row md:items-end">
                                <div className="max-w-3xl">
                                    {homepage.newArrivalsEyebrow && <p className="text-xs font-black tracking-[0.22em] text-red-600 uppercase">{homepage.newArrivalsEyebrow}</p>}
                                    <h2 className="mt-4 text-4xl font-black tracking-[-0.04em] sm:text-6xl">{homepage.newArrivalsTitle}</h2>
                                    {homepage.newArrivalsDescription && <p className="mt-5 text-lg leading-8 text-black/65">{homepage.newArrivalsDescription}</p>}
                                </div>
                                <Link href="/catalogue" className="shrink-0 text-sm font-black text-red-600">View all products <span aria-hidden="true">→</span></Link>
                            </div>

                            <div className="mt-12 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                                {newArrivals.map(product => (
                                    <article key={product.id} className="group">
                                        <Link href={'/catalogue/' + product.slug} className="relative block aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-black/10">
                                            <span className="absolute left-3 top-3 z-10 rounded-full bg-red-600 px-3 py-1.5 text-[10px] font-black tracking-wider text-white uppercase">New</span>
                                            {product.image ? <img src={product.image} alt={product.name} loading="lazy" className="h-full w-full object-contain p-5 transition duration-300 group-hover:scale-105" /> : <div className="grid h-full place-items-center text-xs font-bold tracking-widest text-black/35 uppercase">Product image</div>}
                                        </Link>
                                        <div className="mt-4 flex items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs font-semibold text-black/50">{product.sku || 'Uncoded'} · {product.variants} variants</p>
                                                <h3 className="mt-1 font-black leading-snug"><Link href={'/catalogue/' + product.slug} className="hover:text-red-600">{product.name}</Link></h3>
                                            </div>
                                            <span className={`mt-1 size-2.5 shrink-0 rounded-full ${product.inStock ? 'bg-red-600' : 'bg-black/20'}`} title={product.inStock ? 'In stock' : 'Out of stock'} />
                                        </div>
                                        {pricing.authorized ? (
                                            product.accountPrice ? <div className="mt-3 flex items-baseline gap-2"><p className="text-lg font-black text-black">From ${product.accountPrice} CAD</p>{product.compareAtPrice && <p className="text-base text-red-600 line-through">${product.compareAtPrice}</p>}{!product.compareAtPrice && product.accountPrice !== product.wholesalePrice && <p className="text-base text-red-600 line-through">${product.wholesalePrice}</p>}</div> : <p className="mt-3 text-sm font-semibold text-black/50">Contact for wholesale pricing</p>
                                        ) : <p className="mt-3 text-sm font-bold text-red-600">Sign in to view wholesale price</p>}
                                    </article>
                                ))}
                            </div>

                            {newArrivals.length === 0 && <div className="mt-12 rounded-3xl border border-dashed border-black/20 p-12 text-center text-black/50">New products will appear here automatically.</div>}
                        </div>
                    </section>
                )}
            </main>
        </PublicShell>
    );
}

function HeroSlider({ images, interval }: { images: string[]; interval: number }) {
    const [active, setActive] = useState(0);
    const [paused, setPaused] = useState(false);
    const hasMultiple = images.length > 1;

    useEffect(() => {
        if (!hasMultiple || paused) return;
        const timer = window.setInterval(() => setActive(current => (current + 1) % images.length), Math.max(interval, 3) * 1000);

        return () => window.clearInterval(timer);
    }, [hasMultiple, images.length, interval, paused]);

    useEffect(() => {
        if (active >= images.length) setActive(0);
    }, [active, images.length]);

    function move(direction: number) {
        setActive(current => (current + direction + images.length) % images.length);
    }

    return (
        <div className="relative min-h-72 overflow-hidden bg-white lg:min-h-full" onMouseEnter={() => setPaused(true)} onMouseLeave={() => setPaused(false)}>
            {images.length > 0 ? images.map((image, index) => (
                <img key={image} src={image} alt={`IGI Canada wholesale collection ${index + 1}`} loading={index === 0 ? 'eager' : 'lazy'} fetchPriority={index === 0 ? 'high' : 'auto'} className={`absolute inset-0 h-full w-full object-contain transition-opacity duration-700 ${index === active ? 'opacity-100' : 'pointer-events-none opacity-0'}`} />
            )) : <div className="absolute inset-0 grid place-items-center bg-[radial-gradient(circle_at_30%_30%,#ffffff,#d80621)] text-sm font-bold tracking-[0.2em] text-black uppercase">Hero images can be uploaded in Admin</div>}
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-stone-950/40 via-transparent to-transparent" />
            <div className="absolute bottom-5 left-5 rounded-xl bg-white/90 px-4 py-3 shadow-xl backdrop-blur sm:bottom-7 sm:left-7">
                <p className="text-[10px] font-bold tracking-[0.14em] text-amber-800 uppercase">Wholesale only</p>
                <p className="mt-1 text-xs font-semibold">Protected pricing for approved wholesale accounts</p>
            </div>
            {hasMultiple && <>
                <div className="absolute right-5 bottom-5 flex gap-2 sm:right-7 sm:bottom-7">
                    <button type="button" onClick={() => move(-1)} aria-label="Previous hero image" className="grid size-9 place-items-center rounded-full bg-black/75 text-lg font-bold text-white backdrop-blur transition hover:bg-red-600">←</button>
                    <button type="button" onClick={() => move(1)} aria-label="Next hero image" className="grid size-9 place-items-center rounded-full bg-black/75 text-lg font-bold text-white backdrop-blur transition hover:bg-red-600">→</button>
                </div>
                <div className="absolute top-5 right-5 flex gap-1.5 rounded-full bg-black/35 p-2 backdrop-blur sm:top-7 sm:right-7" aria-label="Choose hero image">
                    {images.map((image, index) => <button key={image} type="button" onClick={() => setActive(index)} aria-label={`Show hero image ${index + 1}`} aria-current={index === active} className={`h-2 rounded-full transition-all ${index === active ? 'w-6 bg-red-600' : 'w-2 bg-white/80 hover:bg-white'}`} />)}
                </div>
            </>}
        </div>
    );
}
