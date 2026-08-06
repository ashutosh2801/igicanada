import { Head, usePage } from '@inertiajs/react';

export type BreadcrumbItem = { label: string; href?: string };
type SeoDefaults = { siteName: string; baseUrl: string; defaultTitle: string; defaultDescription: string | null; defaultImage: string | null };

type Props = {
    title?: string;
    description?: string | null;
    canonicalPath?: string;
    image?: string | null;
    type?: 'website' | 'product' | 'article';
    noIndex?: boolean;
    breadcrumbs?: BreadcrumbItem[];
    schemas?: Record<string, unknown>[];
};

export default function SeoHead({ title, description, canonicalPath = '/', image, type = 'website', noIndex = false, breadcrumbs = [], schemas = [] }: Props) {
    const { seoDefaults } = usePage<{ seoDefaults: SeoDefaults }>().props;
    const pageTitle = title || seoDefaults.defaultTitle;
    const pageDescription = description || seoDefaults.defaultDescription;
    const canonicalUrl = new URL(canonicalPath, seoDefaults.baseUrl).toString();
    const socialImage = image || seoDefaults.defaultImage;
    const graph = [...schemas];

    if (breadcrumbs.length >= 2) {
        graph.push({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: breadcrumbs.map((item, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: item.label,
                ...(item.href ? { item: new URL(item.href, seoDefaults.baseUrl).toString() } : {}),
            })),
        });
    }

    return <Head title={pageTitle}>
        {pageDescription && <meta head-key="description" name="description" content={pageDescription} />}
        <meta head-key="robots" name="robots" content={noIndex ? 'noindex,follow' : 'index,follow,max-image-preview:large'} />
        <link head-key="canonical" rel="canonical" href={canonicalUrl} />
        <meta head-key="og-type" property="og:type" content={type} />
        <meta head-key="og-title" property="og:title" content={pageTitle} />
        {pageDescription && <meta head-key="og-description" property="og:description" content={pageDescription} />}
        <meta head-key="og-url" property="og:url" content={canonicalUrl} />
        <meta head-key="og-site-name" property="og:site_name" content={seoDefaults.siteName} />
        {socialImage && <meta head-key="og-image" property="og:image" content={socialImage} />}
        <meta head-key="twitter-card" name="twitter:card" content={socialImage ? 'summary_large_image' : 'summary'} />
        <meta head-key="twitter-title" name="twitter:title" content={pageTitle} />
        {pageDescription && <meta head-key="twitter-description" name="twitter:description" content={pageDescription} />}
        {socialImage && <meta head-key="twitter-image" name="twitter:image" content={socialImage} />}
        {graph.map((schema, index) => <script key={index} type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema).replaceAll('<', '\\u003c') }} />)}
    </Head>;
}
