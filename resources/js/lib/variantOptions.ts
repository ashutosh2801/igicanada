export type OptionVariant = {
    id: number;
    color: string | null;
    colorCode: string | null;
    sizes: string[];
};

export type VariantGroup<T extends OptionVariant> = {
    name: string;
    swatch: string;
    variants: T[];
};

export function sizeKeyOf(variant: OptionVariant): string {
    return variant.sizes.join(', ') || 'One size';
}

export function groupVariants<T extends OptionVariant>(variants: T[]): VariantGroup<T>[] {
    const byColor = new Map<string, T[]>();
    for (const variant of variants) {
        const key = variant.color || 'One size';
        byColor.set(key, [...(byColor.get(key) || []), variant]);
    }
    return Array.from(byColor.entries()).map(([name, group]) => {
        const swatch = group.find(variant => variant.colorCode)?.colorCode || swatchFallback(name);
        const sizeVariants = Array.from(new Map(group.map(variant => [sizeKeyOf(variant), variant])).values());
        return { name, swatch, variants: sizeVariants };
    });
}

const LEATHER_SWATCHES: Record<string, string> = {
    black: '#1c1917',
    brown: '#7c4a26',
    'light brown': '#a9743f',
    'dark brown': '#4a2f1d',
    'oil brown': '#4a2f1d',
    'brown oil': '#4a2f1d',
    'hunter brown': '#5f3b22',
    tan: '#c9a06c',
    'dark tan': '#a9743f',
    cognac: '#9a5f2e',
    walnut: '#5f3b22',
    camel: '#b98d5a',
    honey: '#d2a25f',
    rust: '#a3442f',
    beige: '#d8c7a8',
    white: '#f4f4f5',
    grey: '#6b7280',
    gray: '#6b7280',
    red: '#9f1d1d',
    purple: '#6b21a8',
    navy: '#1e3a5f',
    blue: '#1d4ed8',
    turquoise: '#0f766e',
    olive: '#6b6a3a',
};

export function swatchFallback(name: string): string {
    const known = LEATHER_SWATCHES[name.trim().toLowerCase()];
    if (known) return known;
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) % 360;
    return `hsl(${hash}, 45%, 35%)`;
}
