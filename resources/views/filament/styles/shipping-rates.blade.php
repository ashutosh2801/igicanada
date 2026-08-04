<style>
    .shipping-rate-headings {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 1.5rem;
        padding: 0 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-600);
    }

    .dark .shipping-rate-headings {
        color: var(--gray-300);
    }

    .shipping-rates-zebra .fi-fo-repeater-items > .fi-fo-repeater-item:nth-child(odd) {
        background-color: var(--gray-50);
    }

    .shipping-rates-zebra .fi-fo-repeater-items > .fi-fo-repeater-item:nth-child(even) {
        background-color: white;
    }

    .dark .shipping-rates-zebra .fi-fo-repeater-items > .fi-fo-repeater-item:nth-child(odd) {
        background-color: color-mix(in srgb, var(--gray-900) 75%, black);
    }

    .dark .shipping-rates-zebra .fi-fo-repeater-items > .fi-fo-repeater-item:nth-child(even) {
        background-color: var(--gray-900);
    }

    @media (max-width: 63.999rem) {
        .shipping-rate-headings {
            display: none;
        }
    }
</style>
