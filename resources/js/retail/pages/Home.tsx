import RetailShell from '../components/RetailShell';
import SeoHead from '@/components/SeoHead';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Product = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    price: string | null;
    compareAtPrice: string | null;
    inStock: boolean;
};

type Category = {
    name: string;
    slug: string;
    image: string | null;
};

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
    categoriesEyebrow: string | null;
    categoriesTitle: string;
    categoriesDescription: string | null;
    showNewArrivals: boolean;
    newArrivalsEyebrow: string | null;
    newArrivalsTitle: string;
    newArrivalsDescription: string | null;
    metaTitle: string;
    metaDescription: string | null;
};

type Props = { homepage: Homepage; products: Product[]; categories: Category[] };

export default function Home({ homepage, products, categories }: Props) {
    const heroProducts = products.filter((product) => product.image).slice(0, 3);
    const heroSlides = homepage.heroImages.map((src) => ({ src, cover: true }));

    return (
        <RetailShell>
            <SeoHead title={homepage.metaTitle} description={homepage.metaDescription} canonicalPath="/" schemas={[{
                '@context': 'https://schema.org',
                '@type': 'WebSite',
                name: 'Leather Wallets Canada',
                url: 'https://leatherwallets.ca/',
            }, {
                '@context': 'https://schema.org',
                '@type': 'Organization',
                name: 'Leather Wallets Canada',
                url: 'https://leatherwallets.ca/',
            }]} />

            <main className="bg-white">
                <section className="relative isolate min-h-[54vh] overflow-hidden bg-[#d80621] sm:min-h-[58.5vh]">
                    <HeroBackdrop images={heroSlides} fallbackProducts={heroProducts} interval={homepage.heroSliderInterval} />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-black/15" />
                    <div className="relative mx-auto flex min-h-[54vh] max-w-7xl items-end justify-center px-6 py-16 text-center text-white sm:min-h-[58.5vh] sm:py-20 lg:px-8">
                        <div className="max-w-3xl">
                            {homepage.heroEyebrow && <p className="text-[11px] font-semibold tracking-[0.34em] uppercase">{homepage.heroEyebrow}</p>}
                            <h1 className="font-display mt-5 text-5xl leading-[0.94] font-normal tracking-[-0.04em] text-balance sm:text-7xl lg:text-[5.5rem]">{homepage.heroTitle}</h1>
                            {homepage.heroDescription && <p className="mx-auto mt-5 max-w-xl text-sm leading-6 text-white/85 sm:text-base">{homepage.heroDescription}</p>}
                            <div className="mt-8 flex flex-wrap justify-center gap-7">
                                {homepage.heroPrimaryLabel && homepage.heroPrimaryUrl && <Link href={homepage.heroPrimaryUrl} className="inline-flex border-b border-white pb-1 text-xs font-bold tracking-[0.22em] uppercase transition hover:border-transparent">{homepage.heroPrimaryLabel}</Link>}
                                {homepage.heroSecondaryLabel && homepage.heroSecondaryUrl && <Link href={homepage.heroSecondaryUrl} className="inline-flex border-b border-white/50 pb-1 text-xs font-bold tracking-[0.22em] uppercase transition hover:border-white">{homepage.heroSecondaryLabel}</Link>}
                            </div>
                        </div>
                    </div>
                </section>

                {categories.length > 0 && <section className="mx-auto max-w-[90rem] px-5 py-10 sm:px-8 sm:py-15">
                    <SectionHeading eyebrow="" title={homepage.categoriesTitle || 'Shop by category'} href="/shop" />
                    {homepage.categoriesDescription && <p className="mx-auto mt-4 max-w-2xl text-center text-sm leading-6 text-stone-500">{homepage.categoriesDescription}</p>}
                    <div className="mt-14 grid grid-cols-2 gap-x-7 gap-y-14 sm:grid-cols-3 sm:gap-x-8 sm:gap-y-16 lg:grid-cols-4 lg:gap-x-10 xl:grid-cols-6">
                        {categories.slice(0, 6).map((category) => (
                            <Link key={category.slug} href={`/shop?category=${encodeURIComponent(category.slug)}`} className="group text-center">
                                <div className="mx-auto aspect-[4/5] w-full max-w-44 rounded-none overflow-hidden bg-[#f5f5f5]">
                                    {category.image ? <img src={category.image} alt={category.name} className="h-full w-full object-contain object-center p-3 transition duration-700 group-hover:scale-105 sm:p-4" /> : <div className="grid h-full place-items-center font-display text-4xl text-[#d80621]/25">LW</div>}
                                </div>
                                <h3 className="mt-4 text-xs font-semibold tracking-[0.14em] uppercase">{category.name}</h3>
                            </Link>
                        ))}
                    </div>
                </section>}

                {homepage.showNewArrivals && <section id="new-arrivals" className="border-y border-black/10 bg-[#f7f7f7] py-16 sm:py-15">
                    <div className="mx-auto max-w-[90rem] px-5 sm:px-8">
                        <SectionHeading eyebrow={homepage.newArrivalsEyebrow || 'Just in'} title={homepage.newArrivalsTitle} href="/shop" />
                        {homepage.newArrivalsDescription && <p className="mx-auto mt-4 max-w-2xl text-center text-sm leading-6 text-stone-500">{homepage.newArrivalsDescription}</p>}

                        {products.length > 0 ? (
                            <div className="mt-11 grid grid-cols-2 gap-x-4 gap-y-12 md:grid-cols-3 lg:grid-cols-4 lg:gap-x-6">
                                {products.slice(0, 8).map((product) => <ProductCard key={product.id} product={product} />)}
                            </div>
                        ) : (
                            <div className="mt-10 border border-dashed border-black/20 bg-white px-6 py-16 text-center">
                                <p className="font-display text-2xl">The retail edit is being curated.</p>
                                <p className="mt-2 text-sm text-stone-500">Retail-ready products will appear here automatically.</p>
                            </div>
                        )}
                    </div>
                </section>}

                <section className="mx-auto grid max-w-[90rem] gap-px bg-white px-5 py-16 sm:px-8 sm:py-15 lg:grid-cols-2">
                    <EditorialCard image={products[0]?.image} eyebrow="The craft edit" title="Made to be carried, designed to age beautifully." href={products[0] ? `/products/${products[0].slug}` : '/shop'} />
                    <EditorialCard image="/upload/post//New%20Images/New%20Bag/7070/7070-black-front-hang.jpg" eyebrow="Everyday icons" title="Small essentials. Considered details." href="/shop?category=bags" dark />
                </section>

                <section className="border-y border-black/10 bg-white">
                    <div className="mx-auto grid max-w-7xl divide-y divide-black/10 px-6 sm:grid-cols-3 sm:divide-x sm:divide-y-0 lg:px-8">
                        <Service title="Authentic leather" copy="Selected materials and practical craftsmanship." />
                        <Service title="Ships from Canada" copy="Carefully packed and dispatched from Mississauga." />
                        <Service title="Secure checkout" copy="Protected payments with clear order updates." />
                    </div>
                </section>

                <section className="border-t-8 border-[#d80621] bg-black px-6 py-20 text-center text-white sm:py-28">
                    <p className="text-[10px] font-bold tracking-[0.3em] text-[#ef233c] uppercase">Leather notes</p>
                    <h2 className="font-display mx-auto mt-5 max-w-3xl text-4xl font-normal tracking-tight sm:text-6xl">Objects for everyday rituals.</h2>
                    <p className="mx-auto mt-5 max-w-xl text-sm leading-6 text-white/60">A focused collection of wallets and leather accessories chosen for character, utility and lasting appeal.</p>
                    <Link href="/shop" className="mt-8 inline-flex border-b border-white pb-1 text-xs font-bold tracking-[0.2em] uppercase">Discover the collection</Link>
                </section>
            </main>
        </RetailShell>
    );
}

function HeroBackdrop({ images, fallbackProducts, interval }: { images: { src: string; cover: boolean }[]; fallbackProducts: Product[]; interval: number }) {
    const [active, setActive] = useState(0);

    useEffect(() => {
        if (images.length < 2) return;
        const timer = window.setInterval(() => setActive(current => (current + 1) % images.length), interval * 1000);
        return () => window.clearInterval(timer);
    }, [images.length, interval]);

    if (images.length > 0) {
        return <div className="absolute inset-0">
            {images.map((image, index) => <img key={image.src} src={image.src} alt="" className={`absolute inset-0 h-full w-full bg-[#d80621] transition-opacity duration-1000 ${index === active ? 'opacity-100' : 'opacity-0'} ${image.cover ? 'object-cover object-center' : 'object-contain object-center p-6 sm:p-10'}`} />)}
            {images.length > 1 && <div className="absolute right-6 bottom-6 z-10 flex gap-2">{images.map((image, index) => <button key={image.src} type="button" onClick={() => setActive(index)} aria-label={`Show hero slide ${index + 1}`} className={`h-0.5 transition-all ${index === active ? 'w-8 bg-[#d80621]' : 'w-4 bg-white/70'}`} />)}</div>}
        </div>;
    }

    return <div className="absolute inset-0 grid grid-cols-3 gap-px bg-black">
        {fallbackProducts.length > 0 ? fallbackProducts.map((product, index) => (
            <div key={product.id} className={`relative overflow-hidden bg-white ${index === 2 ? 'hidden sm:block' : ''}`}><img src={product.image!} alt="" className="h-full w-full object-cover object-center" /></div>
        )) : <div className="col-span-3 bg-[radial-gradient(circle_at_28%_15%,#ffffff,#d80621_52%,#090909)]" />}
    </div>;
}

function SectionHeading({ eyebrow, title, href }: { eyebrow: string; title: string; href: string }) {
    return <div className="relative text-center">
        {eyebrow && <p className="text-[10px] font-bold tracking-[0.28em] text-[#d80621] uppercase">{eyebrow}</p>}
        <h2 className="font-display mt-3 text-3xl font-normal tracking-[-0.025em] sm:text-5xl">{title}</h2>
        <Link href={href} className="mt-4 inline-block text-[10px] font-bold tracking-[0.18em] uppercase underline underline-offset-4 sm:absolute sm:right-0 sm:bottom-1 sm:mt-0">View all</Link>
    </div>;
}

function ProductCard({ product }: { product: Product }) {
    return <article className="group">
        <Link href={`/products/${product.slug}`} className="relative block aspect-[3/4] overflow-hidden bg-white">
            {product.image ? <img src={product.image} alt={product.name} className="h-full w-full object-contain p-5 transition duration-700 group-hover:scale-[1.04]" /> : <div className="grid h-full place-items-center text-xs tracking-widest text-black/25 uppercase">Leather Wallets</div>}
            <span className="absolute top-3 left-3 bg-[#d80621] px-2.5 py-1 text-[9px] font-bold tracking-[0.15em] text-white uppercase">New</span>
        </Link>
        <div className="mt-4 text-center">
            <h3 className="min-h-10 text-sm leading-5"><Link href={`/products/${product.slug}`}>{product.name}</Link></h3>
            <p className="mt-2 text-xs font-semibold">{product.price ? <span className="inline-flex items-baseline gap-2">${product.price} CAD{product.compareAtPrice && <span className="text-stone-400 line-through">${product.compareAtPrice}</span>}</span> : 'Price coming soon'}</p>
        </div>
    </article>;
}

function EditorialCard({ image, eyebrow, title, href, dark = false }: { image?: string | null; eyebrow: string; title: string; href: string; dark?: boolean }) {
    return <Link href={href} className={`group relative isolate min-h-[32rem] overflow-hidden ${dark ? 'bg-black' : 'bg-[#d80621]'}`}>
        {image && <img src={image} alt="" className={`absolute inset-0 h-full w-full object-cover object-center transition duration-1000 group-hover:scale-105 ${dark ? 'opacity-70' : 'opacity-80 mix-blend-multiply'}`} />}
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" />
        <div className="relative flex min-h-[32rem] items-end p-8 text-white sm:p-12">
            <div><p className="text-[10px] font-bold tracking-[0.25em] uppercase">{eyebrow}</p><h2 className="font-display mt-3 max-w-lg text-4xl leading-tight sm:text-5xl">{title}</h2><span className="mt-6 inline-block border-b border-white pb-1 text-[10px] font-bold tracking-[0.2em] uppercase">Shop now</span></div>
        </div>
    </Link>;
}

function Service({ title, copy }: { title: string; copy: string }) {
    return <div className="px-6 py-9 text-center"><h3 className="text-xs font-bold tracking-[0.16em] uppercase">{title}</h3><p className="mt-2 text-xs leading-5 text-stone-500">{copy}</p></div>;
}
