import PublicShell from '@/components/PublicShell';
import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import { groupVariants, sizeKeyOf } from '@/lib/variantOptions';
import { Link, router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

type Variant = {
    id: number;
    color: string | null;
    colorCode: string | null;
    sizes: string[];
    images: { src: string; alt: string }[];
    inStock: boolean;
    stockQuantity: number | null;
    minimumQuantity: number;
    wholesalePrice: string | null;
    accountPrice: string | null;
};

type SimilarProduct = {
    id: number;
    sku: string | null;
    name: string;
    slug: string;
    image: string | null;
    variants: number;
    inStock: boolean;
    wholesalePrice: string | null;
    accountPrice: string | null;
};

type Props = {
    product: {
        name: string;
        slug: string;
        sku: string | null;
        description: string;
        seoDescription: string;
        categories: { name: string; slug: string }[];
        images: { src: string; alt: string }[];
        variants: Variant[];
    };
    pricing: { authorized: boolean; tier: string | null; discountPercentage: string | null };
    similarProducts: SimilarProduct[];
};

export default function Show({ product, pricing, similarProducts }: Props) {
    const [selectedImageIndex, setSelectedImageIndex] = useState(0);
    const [isZoomed, setIsZoomed] = useState(false);
    const [isAutoplayPaused, setIsAutoplayPaused] = useState(false);
    const [zoomOrigin, setZoomOrigin] = useState({ x: 50, y: 50 });
    const thumbnailTrack = useRef<HTMLDivElement>(null);
    const category = product.categories[0];
    const breadcrumbs: BreadcrumbItem[] = [
        { label: 'Home', href: '/' },
        { label: 'Wholesale catalogue', href: '/catalogue' },
        ...(category ? [{ label: category.name, href: `/catalogue?category=${encodeURIComponent(category.slug)}` }] : []),
        { label: product.name },
    ];

    const groups = useMemo(() => groupVariants(product.variants), [product.variants]);
    const hasColors = product.variants.some(variant => variant.color !== null);
    const needsPicker = groups.length > 1 || groups.some(group => group.variants.length > 1);
    const initialVariant = product.variants.find(variant => variant.inStock) || product.variants[0];
    const [colorKey, setColorKey] = useState<string>(initialVariant?.color || 'One size');
    const [sizeKey, setSizeKey] = useState<string>(initialVariant ? sizeKeyOf(initialVariant) : 'One size');
    const [quantity, setQuantity] = useState(initialVariant?.minimumQuantity ?? 1);
    const [isAdding, setIsAdding] = useState(false);

    const activeGroup = groups.find(group => group.name === colorKey) || groups[0];
    const galleryImages = useMemo(() => activeGroup?.variants.find(variant => variant.images.length > 0)?.images || product.images, [activeGroup, product.images]);
    const selectedImage = galleryImages[selectedImageIndex] || galleryImages[0];
    const selected = activeGroup?.variants.find(variant => variant.inStock && sizeKeyOf(variant) === sizeKey)
        || activeGroup?.variants.find(variant => sizeKeyOf(variant) === sizeKey)
        || activeGroup?.variants.find(variant => variant.inStock)
        || activeGroup?.variants[0]
        || initialVariant;
    const maximumQuantity = selected ? (selected.stockQuantity ?? selected.minimumQuantity) : 0;
    const canOrder = Boolean(selected && selected.inStock && maximumQuantity >= selected.minimumQuantity);

    useEffect(() => {
        setQuantity(current => Math.max(selected?.minimumQuantity ?? 1, Math.min(current, maximumQuantity)));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected?.id]);

    function chooseColor(name: string) {
        setColorKey(name);
        setSelectedImageIndex(0);
        const group = groups.find(group => group.name === name);
        const next = group?.variants.find(variant => variant.inStock) || group?.variants[0];
        if (next) setSizeKey(sizeKeyOf(next));
    }

    function updateQuantity(value: number) {
        setQuantity(Math.min(maximumQuantity, Math.max(selected?.minimumQuantity ?? 1, value)));
    }

    function addToCart() {
        if (!selected || !canOrder) return;
        router.post('/cart/items', { variant_id: selected.id, quantity }, {
            preserveScroll: true,
            onStart: () => setIsAdding(true),
            onFinish: () => setIsAdding(false),
        });
    }

    useEffect(() => {
        if (galleryImages.length <= 1 || isAutoplayPaused) return;

        const timer = window.setTimeout(() => {
            setIsZoomed(false);
            setZoomOrigin({ x: 50, y: 50 });
            setSelectedImageIndex((current) => (current + 1) % galleryImages.length);
        }, 5000);

        return () => window.clearTimeout(timer);
    }, [selectedImageIndex, isAutoplayPaused, galleryImages.length]);

    useEffect(() => {
        const thumbnail = thumbnailTrack.current?.children.item(selectedImageIndex);
        if (thumbnail instanceof HTMLElement) {
            thumbnail.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }, [selectedImageIndex]);

    function selectImage(index: number) {
        setSelectedImageIndex(index);
        setIsZoomed(false);
        setZoomOrigin({ x: 50, y: 50 });
    }

    function slideThumbnails(direction: -1 | 1) {
        const track = thumbnailTrack.current;
        if (!track) return;
        track.scrollBy({ left: direction * track.clientWidth * 0.8, behavior: 'smooth' });
    }

    return (
        <PublicShell>
            <SeoHead title={`${product.name} wholesale`} description={product.seoDescription} canonicalPath={`/catalogue/${product.slug}`} image={product.images[0]?.src} type="product" breadcrumbs={breadcrumbs} schemas={[{
                '@context': 'https://schema.org',
                '@type': 'Product',
                name: product.name,
                sku: product.sku || undefined,
                description: product.seoDescription,
                image: product.images.map(image => image.src),
            }]} />
                <main className="mx-auto grid min-h-[40rem] max-w-7xl gap-12 px-6 py-12 lg:grid-cols-2 lg:px-8">
                    <div className="lg:col-span-2"><Breadcrumbs items={breadcrumbs} /></div>
                    <section>
                        <div
                            className={'aspect-square overflow-hidden rounded-3xl bg-white ring-1 ring-stone-200 ' + (selectedImage ? (isZoomed ? 'cursor-zoom-out' : 'cursor-zoom-in') : '')}
                            onMouseEnter={() => {
                                setIsAutoplayPaused(true);
                                if (selectedImage) setIsZoomed(true);
                            }}
                            onMouseLeave={() => {
                                setIsAutoplayPaused(false);
                                setIsZoomed(false);
                            }}
                            onMouseMove={(event) => {
                                if (!selectedImage) return;
                                const bounds = event.currentTarget.getBoundingClientRect();
                                setZoomOrigin({
                                    x: ((event.clientX - bounds.left) / bounds.width) * 100,
                                    y: ((event.clientY - bounds.top) / bounds.height) * 100,
                                });
                            }}
                        >
                            {selectedImage ? <img src={selectedImage.src} alt={selectedImage.alt} draggable={false} className="h-full w-full select-none object-contain p-6 transition-transform duration-200 ease-out" style={{ transform: isZoomed ? 'scale(2)' : 'scale(1)', transformOrigin: `${zoomOrigin.x}% ${zoomOrigin.y}%` }} /> : <div className="grid h-full place-items-center text-stone-400">Image unavailable</div>}
                        </div>
                        {galleryImages.length > 1 && (
                            <div className="mt-4 flex items-center gap-2" onMouseEnter={() => setIsAutoplayPaused(true)} onMouseLeave={() => setIsAutoplayPaused(false)}>
                                {galleryImages.length > 5 && <button type="button" onClick={() => slideThumbnails(-1)} aria-label="Show previous thumbnails" className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-xl font-bold shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-100">‹</button>}
                                <div ref={thumbnailTrack} className="flex min-w-0 flex-1 snap-x gap-3 overflow-x-auto scroll-smooth py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                    {galleryImages.map((image, index) => (
                                        <button
                                            key={`${image.src}-${index}`}
                                            type="button"
                                            onClick={() => selectImage(index)}
                                            aria-label={`View image ${index + 1} of ${galleryImages.length}`}
                                            aria-pressed={selectedImageIndex === index}
                                            className={'aspect-square w-20 shrink-0 snap-start overflow-hidden rounded-xl bg-white p-2 transition ring-2 sm:w-24 ' + (selectedImageIndex === index ? 'ring-red-600' : 'ring-stone-200 hover:ring-stone-400')}
                                        >
                                            <img src={image.src} alt={image.alt} className="h-full w-full object-contain" />
                                        </button>
                                    ))}
                                </div>
                                {galleryImages.length > 5 && <button type="button" onClick={() => slideThumbnails(1)} aria-label="Show next thumbnails" className="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-xl font-bold shadow-sm ring-1 ring-stone-200 transition hover:bg-stone-100">›</button>}
                            </div>
                        )}
                    </section>
                    <section>
                        <div className="flex flex-wrap gap-2">
                            {product.categories.map((category) => <Link key={category.slug} href="/catalogue" data={{ category: category.slug }} className="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">{category.name}</Link>)}
                        </div>
                        <p className="mt-6 text-sm font-semibold text-stone-500">{product.sku || 'Product code unavailable'}</p>
                        <h1 className="mt-2 text-4xl font-black tracking-tight">{product.name}</h1>
                        {product.description && (
                            <div
                                className="mt-5 space-y-3 leading-7 text-stone-600 [&_a]:font-semibold [&_a]:text-red-600 [&_a]:underline [&_h2]:text-2xl [&_h2]:font-black [&_h3]:text-xl [&_h3]:font-bold [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-3 [&_ul]:list-disc [&_ul]:pl-6"
                                dangerouslySetInnerHTML={{ __html: product.description }}
                            />
                        )}

                        {!pricing.authorized && (
                            <div className="mt-8 rounded-2xl bg-stone-950 p-6 text-white">
                                <h2 className="text-lg font-bold">Wholesale pricing is protected</h2>
                                <p className="mt-2 text-sm leading-6 text-stone-300">Approved wholesale accounts can view account pricing, stock quantities and order minimums.</p>
                                <Link href="/login" className="mt-5 inline-flex rounded-full bg-amber-700 px-5 py-2.5 text-sm font-bold">Wholesale sign in</Link>
                            </div>
                        )}

                        {pricing.authorized && <p className="mt-8 rounded-xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{pricing.tier || 'Standard wholesale'} pricing applied{pricing.discountPercentage !== '0.00' ? ' · ' + pricing.discountPercentage + '% tier discount' : ''}</p>}

                        <div className="mt-7 rounded-2xl bg-white ring-1 ring-stone-200">
                            {!needsPicker ? (
                                product.variants.map(variant => <VariantRow key={variant.id} variant={variant} pricingAuthorized={pricing.authorized} />)
                            ) : (
                                <div className="p-5 sm:p-6">
                                    {hasColors && groups.length > 1 && (
                                        <div>
                                            <p className="text-sm font-bold tracking-wide text-stone-500">Colour</p>
                                            <div className="mt-3 flex flex-wrap gap-4">
                                                {groups.map(group => (
                                                    <button key={group.name} type="button" onClick={() => chooseColor(group.name)} aria-pressed={colorKey === group.name} className="group flex flex-col items-center gap-1.5" title={group.name}>
                                                        <span className={'size-10 rounded-full ring-offset-2 transition ' + (colorKey === group.name ? 'ring-2 ring-red-600' : 'ring-1 ring-stone-300 group-hover:ring-2 group-hover:ring-stone-400')} style={{ backgroundColor: group.swatch }} />
                                                        <span className={'text-xs font-bold ' + (colorKey === group.name ? 'text-red-700' : 'text-stone-500 group-hover:text-stone-700')}>{group.name}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {selected && (
                                        <>
                                            <div className="mt-6">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <p className="text-sm font-bold tracking-wide text-stone-500">Size</p>
                                                    <p className="text-sm text-stone-500">{selected.inStock ? (pricing.authorized ? selected.stockQuantity + ' available' : 'In stock') : 'Out of stock'} · Min. {selected.minimumQuantity}</p>
                                                </div>
                                                <div className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                                    {(activeGroup?.variants ?? []).map(variant => {
                                                        const key = sizeKeyOf(variant);
                                                        const isSelected = sizeKey === key;
                                                        return (
                                                            <button key={variant.id} type="button" disabled={!variant.inStock} onClick={() => setSizeKey(key)} className={'rounded-xl border p-3 text-left transition ' + (isSelected ? 'border-red-600 bg-red-50 ring-1 ring-red-600' : 'border-stone-200 bg-white hover:border-stone-300') + (variant.inStock ? '' : ' cursor-not-allowed opacity-40')}>
                                                                <p className="font-bold">{key}</p>
                                                                {pricing.authorized ? <p className="mt-0.5 text-sm font-bold text-red-600">${variant.accountPrice} CAD</p> : <p className="mt-0.5 text-xs text-stone-400">{variant.inStock ? 'In stock' : 'Out of stock'}</p>}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                            {pricing.authorized ? (
                                                <div className="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-stone-200 pt-5">
                                                    <div>
                                                        <p className="text-xl font-black">{'$' + selected.accountPrice + ' CAD'}</p>
                                                        {selected.accountPrice !== selected.wholesalePrice && <p className="text-xs text-stone-400 line-through">{'$' + selected.wholesalePrice}</p>}
                                                    </div>
                                                    <div className="flex flex-wrap items-end gap-3">
                                                        <span className="flex overflow-hidden rounded-full ring-1 ring-stone-300">
                                                            <button type="button" onClick={() => updateQuantity(quantity - 1)} disabled={!canOrder || quantity <= selected.minimumQuantity} aria-label="Decrease quantity" className="h-9 w-9 bg-stone-100 font-bold disabled:cursor-not-allowed disabled:text-stone-300">−</button>
                                                            <input type="number" min={selected.minimumQuantity} max={maximumQuantity} value={quantity} onChange={(event) => updateQuantity(Number(event.target.value) || selected.minimumQuantity)} disabled={!canOrder} aria-label="Quantity" className="h-9 w-16 appearance-none border-x border-stone-200 bg-white text-center text-sm font-bold outline-none [appearance:textfield] disabled:bg-stone-100 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                                            <button type="button" onClick={() => updateQuantity(quantity + 1)} disabled={!canOrder || quantity >= maximumQuantity} aria-label="Increase quantity" className="h-9 w-9 bg-stone-100 font-bold disabled:cursor-not-allowed disabled:text-stone-300">+</button>
                                                        </span>
                                                        <button type="button" disabled={!canOrder || isAdding} onClick={addToCart} className="h-9 rounded-full bg-stone-950 px-5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">{isAdding ? 'Adding…' : 'Add to cart'}</button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="mt-6 border-t border-stone-200 pt-5">
                                                    <p className="text-sm font-semibold text-red-600">Sign in to view wholesale price and order this size.</p>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    </section>
                    {similarProducts.length > 0 && (
                        <section className="border-t border-stone-200 pt-12 lg:col-span-2">
                            <div className="flex items-end justify-between gap-5">
                                <div><p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">You may also like</p><h2 className="mt-2 text-3xl font-black tracking-tight">Similar products</h2></div>
                                <Link href="/catalogue" className="shrink-0 text-sm font-bold text-red-600">View all products →</Link>
                            </div>
                            <div className="mt-7 grid gap-x-5 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                                {similarProducts.map((item) => (
                                    <article key={item.id} className="group">
                                        <Link href={'/catalogue/' + item.slug} className="block aspect-square overflow-hidden rounded-2xl bg-white ring-1 ring-stone-200">
                                            {item.image ? <img src={item.image} alt={item.name} loading="lazy" className="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-105" /> : <div className="grid h-full place-items-center text-sm text-stone-400">Image unavailable</div>}
                                        </Link>
                                        <div className="mt-4 flex items-start justify-between gap-4">
                                            <div><p className="text-xs font-semibold text-stone-500">{item.sku || 'Uncoded'} · {item.variants} variants</p><h3 className="mt-1 font-bold leading-snug"><Link href={'/catalogue/' + item.slug}>{item.name}</Link></h3></div>
                                            <span className={'mt-1 size-2 shrink-0 rounded-full ' + (item.inStock ? 'bg-emerald-500' : 'bg-stone-300')} title={item.inStock ? 'In stock' : 'Out of stock'} />
                                        </div>
                                        {pricing.authorized ? (
                                            item.accountPrice ? <div className="mt-3 flex items-baseline gap-2"><p className="font-black text-red-600">From ${item.accountPrice} CAD</p>{item.accountPrice !== item.wholesalePrice && <p className="text-xs text-black/40 line-through">${item.wholesalePrice}</p>}</div> : <p className="mt-3 text-sm font-semibold text-black/50">Contact for wholesale pricing</p>
                                        ) : <p className="mt-3 text-sm font-semibold text-red-600">Sign in to view wholesale price</p>}
                                    </article>
                                ))}
                            </div>
                        </section>
                    )}
                </main>
        </PublicShell>
    );
}

function VariantRow({ variant, pricingAuthorized }: { variant: Variant; pricingAuthorized: boolean }) {
    const maximumQuantity = variant.stockQuantity ?? variant.minimumQuantity;
    const canOrder = variant.inStock && maximumQuantity >= variant.minimumQuantity;
    const [quantity, setQuantity] = useState(variant.minimumQuantity);
    const [isAdding, setIsAdding] = useState(false);

    function updateQuantity(value: number) {
        setQuantity(Math.min(maximumQuantity, Math.max(variant.minimumQuantity, value)));
    }

    function addToCart() {
        if (!canOrder) return;
        router.post('/cart/items', { variant_id: variant.id, quantity }, {
            preserveScroll: true,
            onStart: () => setIsAdding(true),
            onFinish: () => setIsAdding(false),
        });
    }

    return (
        <div className="flex flex-col justify-between gap-5 p-5 sm:flex-row sm:items-center">
            <div>
                <div className="flex items-center gap-2.5">
                    {variant.colorCode && <span className="size-5 shrink-0 rounded-full ring-1 ring-black/15 ring-offset-2" style={{ backgroundColor: variant.colorCode }} aria-hidden="true" />}
                    <p className="font-bold">{variant.color || (variant.sizes.length === 0 ? 'Standard' : 'Available sizes')}</p>
                </div>
                {variant.sizes.length > 0 && (
                    <div className="mt-2 flex flex-wrap gap-1.5" aria-label="Available sizes">
                        {variant.sizes.map((size) => <span key={size} className="rounded-md bg-stone-100 px-2.5 py-1 text-xs font-bold text-stone-700 ring-1 ring-stone-200">{size}</span>)}
                    </div>
                )}
                <p className="mt-1 text-sm text-stone-500">{variant.inStock ? (pricingAuthorized ? variant.stockQuantity + ' available' : 'In stock') : 'Out of stock'} · Min. {variant.minimumQuantity}</p>
            </div>
            {pricingAuthorized && (
                <div className="sm:text-right">
                    <p className="text-xl font-black">{'$' + variant.accountPrice + ' CAD'}</p>
                    {variant.accountPrice !== variant.wholesalePrice && <p className="text-xs text-stone-400 line-through">{'$' + variant.wholesalePrice}</p>}
                    <div className="mt-3 flex flex-wrap items-end gap-3 sm:justify-end">
                        <span className="flex overflow-hidden rounded-full ring-1 ring-stone-300">
                            <button type="button" onClick={() => updateQuantity(quantity - 1)} disabled={!canOrder || quantity <= variant.minimumQuantity} aria-label="Decrease quantity" className="h-9 w-9 bg-stone-100 font-bold disabled:cursor-not-allowed disabled:text-stone-300">−</button>
                            <input type="number" min={variant.minimumQuantity} max={maximumQuantity} value={quantity} onChange={(event) => updateQuantity(Number(event.target.value) || variant.minimumQuantity)} disabled={!canOrder} aria-label="Quantity" className="h-9 w-16 appearance-none border-x border-stone-200 bg-white text-center text-sm font-bold outline-none [appearance:textfield] disabled:bg-stone-100 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                            <button type="button" onClick={() => updateQuantity(quantity + 1)} disabled={!canOrder || quantity >= maximumQuantity} aria-label="Increase quantity" className="h-9 w-9 bg-stone-100 font-bold disabled:cursor-not-allowed disabled:text-stone-300">+</button>
                        </span>
                        <button type="button" disabled={!canOrder || isAdding} onClick={addToCart} className="h-9 rounded-full bg-stone-950 px-5 text-xs font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-300">{isAdding ? 'Adding…' : 'Add to cart'}</button>
                    </div>
                </div>
            )}
        </div>
    );
}
