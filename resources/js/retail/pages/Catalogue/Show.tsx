import RetailShell from '../../components/RetailShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { groupVariants, sizeKeyOf } from '@/lib/variantOptions';
import { Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

type Variant = { id: number; label: string; color: string | null; colorCode: string | null; sizes: string[]; images: { src: string; alt: string }[]; price: string; compareAtPrice: string | null; stockQuantity: number; inStock: boolean };
type Props = { product: { name: string; slug: string; sku: string | null; description: string; seoDescription: string; categories: { name: string; slug: string }[]; images: { src: string; alt: string }[]; variants: Variant[] } };

type VariantGroup = { name: string; swatch: string; variants: Variant[] };

export default function ProductShow({ product }: Props) {
    const firstAvailable = product.variants.find(variant => variant.inStock) || product.variants[0];
    const [activeImage, setActiveImage] = useState(0);
    const thumbnailRail = useRef<HTMLDivElement>(null);

    const groups = useMemo(() => groupVariants(product.variants), [product.variants]);

    const hasColors = product.variants.some(variant => variant.color !== null);
    const needsPicker = groups.length > 1 || groups.some(group => group.variants.length > 1);
    const [colorKey, setColorKey] = useState<string>(firstAvailable.color || 'One size');
    const [sizeKey, setSizeKey] = useState<string>(sizeKeyOf(firstAvailable));

    const activeGroup = groups.find(group => group.name === colorKey) || groups[0] || { name: 'One size', swatch: '#78716c', variants: [] as Variant[] };
    const galleryImages = useMemo(() => activeGroup.variants.find(variant => variant.images.length > 0)?.images || product.images, [activeGroup, product.images]);
    const selected = useMemo(() => activeGroup.variants.find(variant => variant.inStock && sizeKeyOf(variant) === sizeKey)
        || activeGroup.variants.find(variant => sizeKeyOf(variant) === sizeKey)
        || activeGroup.variants.find(variant => variant.inStock)
        || activeGroup.variants[0]
        || firstAvailable, [activeGroup, sizeKey, firstAvailable]);
    const form = useForm({ variant_id: selected.id, quantity: 1 });

    function chooseColor(name: string) {
        setColorKey(name);
        setActiveImage(0);
        const group = groups.find(group => group.name === name);
        const next = group?.variants.find(variant => variant.inStock) || group?.variants[0];
        if (next) setSizeKey(sizeKeyOf(next));
    }
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
        form.transform(data => ({ ...data, variant_id: selected.id }));
        form.post('/cart/items', { preserveScroll: true });
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
                            {galleryImages[activeImage] ? <img src={galleryImages[activeImage].src} alt={galleryImages[activeImage].alt} className="h-full w-full object-contain p-8" /> : <div className="grid h-full place-items-center text-sm font-bold tracking-widest text-leather-900/30 uppercase">Product image</div>}
                            {galleryImages.length > 1 && <>
                                <button type="button" onClick={() => showImage(activeImage - 1)} aria-label="Previous product image" className="absolute top-1/2 left-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-2xl text-black shadow-lg ring-1 ring-black/10 transition hover:bg-black hover:text-white">‹</button>
                                <button type="button" onClick={() => showImage(activeImage + 1)} aria-label="Next product image" className="absolute top-1/2 right-4 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/95 text-2xl text-black shadow-lg ring-1 ring-black/10 transition hover:bg-black hover:text-white">›</button>
                                <span className="absolute right-5 bottom-5 rounded-full bg-black/75 px-3 py-1 text-xs font-bold text-white">{activeImage + 1} / {galleryImages.length}</span>
                            </>}
                        </div>
                        {galleryImages.length > 1 && <div className="relative mt-4 px-11">
                            <button type="button" onClick={() => scrollThumbnails(-1)} aria-label="Scroll thumbnails left" className="absolute top-1/2 left-0 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-black text-xl text-white transition hover:bg-leather-700">‹</button>
                            <div ref={thumbnailRail} className="flex snap-x gap-3 overflow-x-auto scroll-smooth py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                {galleryImages.map((image, index) => <button key={`${image.src}-${index}`} type="button" data-thumbnail-index={index} onClick={() => showImage(index)} aria-label={`View product image ${index + 1}`} aria-current={activeImage === index ? 'true' : undefined} className={`size-20 shrink-0 snap-start overflow-hidden rounded-xl bg-white transition ${activeImage === index ? 'ring-2 ring-leather-700 ring-offset-2' : 'opacity-75 ring-1 ring-leather-900/10 hover:opacity-100'}`}><img src={image.src} alt={image.alt} className="h-full w-full object-contain p-2" /></button>)}
                            </div>
                            <button type="button" onClick={() => scrollThumbnails(1)} aria-label="Scroll thumbnails right" className="absolute top-1/2 right-0 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-black text-xl text-white transition hover:bg-leather-700">›</button>
                        </div>}
                    </section>

                    <section className="min-w-0 lg:pt-6" aria-label="Product information">
                        <p className="text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">{product.categories.map(category => category.name).join(' · ') || 'Leather collection'}</p>
                        <h1 className="font-display mt-4 text-4xl leading-tight font-bold tracking-tight sm:text-5xl">{product.name}</h1>
                        {product.sku && <p className="mt-3 text-sm text-stone-500">SKU {product.sku}</p>}
                        <div className="mt-6 flex items-baseline gap-3"><p className="text-2xl font-bold text-leather-700">${selected.price} CAD</p>{selected.compareAtPrice && <p className="text-sm text-stone-400 line-through">${selected.compareAtPrice}</p>}</div>

                        {needsPicker && <div className="mt-8 space-y-7">
                            {hasColors && groups.length > 1 && <div>
                                <p className="text-sm font-bold">Colour</p>
                                <div className="mt-3 flex flex-wrap gap-4">
                                    {groups.map(group => <button key={group.name} type="button" onClick={() => chooseColor(group.name)} aria-pressed={colorKey === group.name} className="group flex flex-col items-center gap-1.5" title={group.name}>
                                        <span className={`size-11 rounded-full transition ${colorKey === group.name ? 'ring-2 ring-leather-700 ring-offset-2' : 'ring-1 ring-leather-900/15 group-hover:ring-2 group-hover:ring-leather-700/50'}`} style={{ backgroundColor: group.swatch }} />
                                        <span className={`text-xs font-bold ${colorKey === group.name ? 'text-leather-700' : 'text-stone-500 group-hover:text-stone-700'}`}>{group.name}</span>
                                    </button>)}
                                </div>
                            </div>}
                            {activeGroup.variants.length > 1 && <div>
                                <p className="text-sm font-bold">Size</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {activeGroup.variants.map(variant => {
                                        const key = sizeKeyOf(variant);
                                        return <button key={variant.id} type="button" disabled={!variant.inStock} onClick={() => setSizeKey(key)} className={`rounded-full border px-5 py-2.5 text-sm font-bold transition ${sizeKey === key ? 'border-leather-900 bg-leather-900 text-white' : 'border-leather-900/15 bg-white text-leather-900'} disabled:cursor-not-allowed disabled:opacity-40`}>{key}</button>;
                                    })}
                                </div>
                                {activeGroup.variants.some(variant => !variant.inStock) && <p className="mt-2 text-xs text-stone-500">Out-of-stock sizes are shown but disabled.</p>}
                            </div>}
                        </div>}

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
