<style>
    .product-sku-sticky {
        position: fixed;
        top: 1rem;
        z-index: 30;
        width: min(28rem, calc(100vw - 2rem));
        padding: 0.75rem;
        border-radius: 0.75rem;
        background: var(--fi-color-white, #ffffff);
        box-shadow: 0 0.25rem 0.75rem rgb(15 23 42 / 0.08);
    }

    html.dark .product-sku-sticky {
        background: var(--fi-color-gray-900, #111827);
    }
</style>
