import Breadcrumbs from '@/components/Breadcrumbs';
import SeoHead, { type BreadcrumbItem } from '@/components/SeoHead';
import RetailShell from '../../components/RetailShell';

type Page = { title: string; slug: string; excerpt: string | null; bodyHtml: string; metaTitle: string | null; metaDescription: string | null; updatedAt: string | null };

export default function ContentPage({ page }: { page: Page }) {
    const breadcrumbs: BreadcrumbItem[] = [{ label: 'Home', href: '/' }, { label: 'Policies', href: '/#policies' }, { label: page.title }];

    return <RetailShell>
        <SeoHead title={page.metaTitle || page.title} description={page.metaDescription || page.excerpt} canonicalPath={`/policies/${page.slug}`} type="article" breadcrumbs={breadcrumbs} />
        <main className="mx-auto max-w-4xl px-6 py-14 lg:px-8 lg:py-20">
            <Breadcrumbs items={breadcrumbs} />
            <p className="mt-10 text-xs font-bold tracking-[0.2em] text-leather-700 uppercase">Store policy</p>
            <h1 className="font-display mt-3 text-5xl font-bold tracking-tight">{page.title}</h1>
            {page.excerpt && <p className="mt-5 text-lg leading-8 text-stone-600">{page.excerpt}</p>}
            <article className="policy-content mt-10 rounded-3xl bg-white p-7 leading-7 text-stone-700 ring-1 ring-leather-900/10 sm:p-10" dangerouslySetInnerHTML={{ __html: page.bodyHtml }} />
            {page.updatedAt && <p className="mt-6 text-xs text-stone-500">Last updated {page.updatedAt}</p>}
        </main>
    </RetailShell>;
}
