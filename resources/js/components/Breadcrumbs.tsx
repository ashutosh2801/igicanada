import { Link } from '@inertiajs/react';
import type { BreadcrumbItem } from './SeoHead';

export default function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
    return <nav aria-label="Breadcrumb" className="mb-7 overflow-x-auto text-sm text-black/55">
        <ol className="flex min-w-max items-center gap-2">
            {items.map((item, index) => <li key={`${item.label}-${index}`} className="flex items-center gap-2">
                {index > 0 && <span aria-hidden="true">/</span>}
                {item.href && index < items.length - 1
                    ? <Link href={item.href} className="transition hover:text-red-600">{item.label}</Link>
                    : <span aria-current={index === items.length - 1 ? 'page' : undefined} className={index === items.length - 1 ? 'font-semibold text-black' : ''}>{item.label}</span>}
            </li>)}
        </ol>
    </nav>;
}
