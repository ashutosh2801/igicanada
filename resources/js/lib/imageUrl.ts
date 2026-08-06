export function resolveImageUrl(value: string): string {
    let candidate = value.replaceAll('&amp;', '&');

    for (let attempt = 0; attempt < 3; attempt += 1) {
        const match = candidate.match(/https:\/\/igicanada\.ca(?=[:/?#]|$)/i);
        if (match?.index !== undefined) {
            return new URL(candidate.slice(match.index)).href;
        }

        try {
            const decoded = decodeURIComponent(candidate);
            if (decoded === candidate) break;
            candidate = decoded;
        } catch {
            break;
        }
    }

    return value;
}

export function installImageUrlNormalizer(): void {
    const normalize = (element: Element): void => {
        const attribute = element.matches('img[src], source[src]')
            ? 'src'
            : element.matches('link[rel~="icon"][href]')
                ? 'href'
                : element.matches('meta[property="og:image"][content], meta[name="twitter:image"][content]')
                    ? 'content'
                    : null;

        if (!attribute) return;

        const current = element.getAttribute(attribute);
        if (!current) return;

        const resolved = resolveImageUrl(current);
        if (resolved !== current) element.setAttribute(attribute, resolved);
    };

    const normalizeTree = (root: ParentNode): void => {
        if (root instanceof Element) normalize(root);
        root.querySelectorAll('img[src], source[src], link[rel~="icon"][href], meta[property="og:image"][content], meta[name="twitter:image"][content]').forEach(normalize);
    };

    normalizeTree(document);
    new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes') normalize(mutation.target as Element);
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) normalizeTree(node);
            });
        }
    }).observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['src', 'href', 'content'],
    });
}
