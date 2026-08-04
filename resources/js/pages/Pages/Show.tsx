import PublicShell from '@/components/PublicShell';
import { Head } from '@inertiajs/react';

type Props = {
    page: {
        title: string;
        excerpt: string | null;
        bodyHtml: string | null;
        metaTitle: string | null;
        metaDescription: string | null;
        updatedAt: string | null;
    };
};

export default function Show({ page }: Props) {
    return (
        <PublicShell>
            <Head title={page.metaTitle || page.title}>
                {page.metaDescription && <meta name="description" content={page.metaDescription} />}
            </Head>
            <main className="mx-auto max-w-4xl px-6 py-16 lg:px-8 lg:py-24">
                <p className="text-sm font-bold tracking-[0.18em] text-amber-800 uppercase">IGI Canada</p>
                <h1 className="mt-3 text-4xl font-black tracking-tight sm:text-6xl">{page.title}</h1>
                {page.excerpt && <p className="mt-6 text-xl leading-8 text-stone-600">{page.excerpt}</p>}
                <article
                    className="mt-12 rounded-3xl bg-white p-7 leading-8 shadow-sm ring-1 ring-stone-900/10 sm:p-12 [&_a]:font-semibold [&_a]:text-amber-800 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-amber-700 [&_blockquote]:pl-5 [&_h2]:mt-10 [&_h2]:text-3xl [&_h2]:font-black [&_h3]:mt-8 [&_h3]:text-2xl [&_h3]:font-bold [&_li]:ml-6 [&_li]:list-disc [&_ol_li]:list-decimal [&_p]:mt-5 [&_table]:mt-6 [&_table]:w-full [&_td]:border [&_td]:border-stone-200 [&_td]:p-3 [&_th]:border [&_th]:border-stone-200 [&_th]:p-3"
                    dangerouslySetInnerHTML={{ __html: page.bodyHtml || '' }}
                />
                {page.updatedAt && <p className="mt-6 text-xs text-stone-500">Last updated {page.updatedAt}</p>}
            </main>
        </PublicShell>
    );
}
