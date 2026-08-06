import RetailShell from '../../components/RetailShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

type Variant = { id: number; label: string; color: string | null; colorCode: string | null; sizes: string[]; price: string; compareAtPrice: string | null; stockQuantity: number; inStock: boolean };
type Props = { product: { name: string; slug: string; sku: string | null; description: string; seoDescription: string; categories: { name: string; slug: string }[]; images: { src: string; alt: string }[]; variants: Variant[] } };

export default function ProductShow({ product }: Props) {
    const firstAvailable = product.variants.find(variant => variant.inStock) || product.variants[0];
    const [variantId, setVariantId] = useState(firstAvailable.id);
    const [activeImage, setActiveImage] = useState(0);
    const thumbnailRail = useRef<HTMLDivElement>(null);
    const selected = useMemo(() => product.variants.find(variant => variant.id === variantId) || firstAvailable, [product.variants, variantId, firstAvailable]);
    const form = useForm({ variant_id: selected.id, quantity: 1 });
    const category = product.categories[0];
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Home', href: '/' },
        { label: 'Shop', href: '/shop' },
        ...(category ? [{ label: category.name, href: `/shop?category=${encodeURIComponent(category.slug)}` }] : []),
        { label: product.name },
    ];
    const prices = product.variants.map(variant => Number(variant.price)).filter(Number.isFinite);

    useEffect(() => {
        const rail = thumbnailRail.current;
        const thumbnail = rail?.querySelector<HTMLElement>(`[data-thumbnail-index="${activeImage}"]`);
        if (!rail || !thumbnail) return;

        rail.scrollTo({
            left: thumbnail.offsetLeft - (rail.clientWidth - thumbnail.clientWidth) / 2,
            behavior: 'smooth',
        });
    }, [activeImage]);

    function addToBag() {
        form.transform(data => ({ ...data, variant_id: selected.id })).post('/cart/items', { preserveScroll: true });
    }

    function showImage(index: number) {
        const total = product.images.length;
        if (!total) return;

        setActiveImage((index + total) % total);
    }

    function scrollThumbnails(direction: -1 | 1) {
        thumbnailRail.current?.scrollBy({
            left: direction * Math.max(240, thumbnailRail.current.clientWidth * 0.75),
            behavior: 'smooth',
        });
    }

    return (
        <RetailShell>
            <SeoHead
                title={product.name}
                description={product.seoDescription}
                canonicalPath={`/products/${product.slug}`}
                image={product.images[0]?.src}
                type="product"
                breadcrumbs={breadcrumbs}
                schemas={[{
                    '@context': 'https://schema.org',
                    '@type': 'Product',
                    name: product.name,
                    sku: product.sku || undefined,
                    description: product.seoDescription,
                    image: product.images.map(image => image.src),
                    offers: {
                        '@type': 'AggregateOffer',
                        priceCurrency: 'CAD',
                        lowPrice: prices.length ? Math.min(...prices).toFixed(2) : undefined,
                        highPrice: prices.length ? Math.max(...prices).toFixed(2) : undefined,
                        offerCount: product.variants.length,
                        availability: product.variants.some(variant => variant.inStock) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    },
                }]}
            />
            <main className="mx-auto max-w-7xl px-6 py-10 lg:px-8 lg:py-16">
                <Breadcrumbs items={breadcrumbs} />
                <div className="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-12">
                    <section className="min-w-0" aria-label="Product images">
                        <div className="group relative aspect-square overflow-hidden rounded-[2rem] bg-white ring-1 ring-leather-900/10">
                            {product.images[activeImage] ? <img src={product.images[activeImage].src} alt={product.images[activeImage].alt} className="h-full w-full object-contain p-8" /> : <div className="grid h-full place-items-center text-sm font-bold tracking-widest text-leather-900/30 uppercase">Product image</div>}
                            {product.images.length > 1 && <>
                                <button type="button" onClick={() => showImage(activeImage - 1)} aria-label="Previous product image" className="absolute top-1/2 left-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-2xl text-black shadow-lg ring-1 ring-black/10 transition hover:bg-black hover:text-white">‹</button>
                                <button type="button" onClick={() => showImage(activeImage + 1)} aria-label="Next product image" className="absolute top-1/2 right-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-2xl text-black shadow-lg ring-1 ring-black/10 transition hover:bg-black hover:text-white">›</button>
                                <span className="absolute right-5 bottom-5 rounded-full bg-black/75 px-3 py-1 text-xs font-bold text-white">{activeImage + 1} / {product.images.length}</span>
                            </>}
                        </div>
                        {product.images.length > 1 && <div className="relative mt-4 px-11">
                            <button type="button" onClick={() => scrollThumbnails(-1)} aria-label="Scroll thumbnails left" className="absolute top-1/2 left-0 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-black text-xl text-white transition hover:bg-leather-700">‹</button>
                            <div ref={thumbnailRail} className="flex snap-x gap-3 overflow-x-auto scroll-smooth py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                {product.images.map((image, index) => <button key={`${image.src}-${index}`} type="button" data-thumbnail-index={index} onClick={() => showImage(index)} aria-label={`View product image ${index + 1}`} aria-current={activeImage === index ? 'true' : undefined} className={`size-20 shrink-0 snap-start overflow-hidden rounded-xl bg-white transition ${activeImage === index ? 'ring-2 ring-leather-700 ring-offset-2' : 'opacity-75 ring-1 ring-leather-900/10 hover:opacity-100'}`}><img src={image.src} alt={image.alt} className="h-full w-full object-contain p-2" /></button>)}
                            </div>
                            <button type="button" onClick={() => scrollThumbnails(1)} aria-label="Scroll thumbnails right" className="absolute top-1/2 right-0 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-black text-xl text-white transition hover:bg-leather-700">›</button>
                        </div>}
                    </section>

                    <section className="min-w-0 lg:pt-6" aria-label="Product information">
                        <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">{product.categories.map(category => category.name).join(' · ') || 'Leather collection'}</p>
                        <h1 className="font-display mt-4 text-4xl leading-tight font-bold tracking-tight sm:text-5xl">{product.name}</h1>
                        {product.sku && <p className="mt-3 text-sm text-stone-500">SKU {product.sku}</p>}
                        <div className="mt-6 flex items-baseline gap-3"><p className="text-2xl font-bold text-leather-700">${selected.price} CAD</p>{selected.compareAtPrice && <p className="text-sm text-stone-400 line-through">${selected.compareAtPrice}</p>}</div>

                        {product.variants.length > 1 && <div className="mt-8"><p className="text-sm font-bold">Choose an option</p><div className="mt-3 grid gap-2 sm:grid-cols-2">{product.variants.map(variant => <button key={variant.id} type="button" disabled={!variant.inStock} onClick={() => { setVariantId(variant.id); form.setData('variant_id', variant.id); }} className={`rounded-xl border px-4 py-3 text-left text-sm font-bold transition ${variant.id === selected.id ? 'border-leather-700 bg-leather-100' : 'border-leather-900/15 bg-white'} disabled:cursor-not-allowed disabled:opacity-40`}>{variant.label}<span className="float-right">${variant.price}</span></button>)}</div></div>}

                        <div className="mt-8 flex gap-3">
                            <input type="number" min="1" max={selected.stockQuantity} value={form.data.quantity} onChange={event => form.setData('quantity', Number(event.target.value))} className="w-20 rounded-full bg-white px-4 py-3 text-center font-bold ring-1 ring-leather-900/15 outline-none focus:ring-2 focus:ring-leather-700" />
                            <button type="button" onClick={addToBag} disabled={!selected.inStock || form.processing} className="flex-1 rounded-full bg-leather-900 px-6 py-3 font-bold text-white transition hover:bg-leather-700 disabled:cursor-not-allowed disabled:opacity-50">{selected.inStock ? (form.processing ? 'Adding…' : 'Add to bag') : 'Sold out'}</button>
                        </div>
                        {form.errors.quantity && <p className="mt-3 text-sm font-bold text-red-700">{form.errors.quantity}</p>}
                        <p className="mt-4 text-xs text-stone-500">{selected.inStock ? `${selected.stockQuantity} available` : 'Currently unavailable'} · Secure checkout</p>

                        {product.description && <div className="prose prose-stone mt-10 border-t border-leather-900/10 pt-8 leading-7 text-stone-600" dangerouslySetInnerHTML={{ __html: product.description }} />}
                    </section>
                </div>
            </main>
        </RetailShell>
    );
}
