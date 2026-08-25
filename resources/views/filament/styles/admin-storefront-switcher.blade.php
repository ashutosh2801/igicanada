<style>
    .admin-storefront-switcher {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-inline: .5rem .25rem;
    }

    .admin-storefront-select-wrap {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid rgb(212 212 216);
        border-radius: .5rem;
        padding-inline-start: .75rem;
        background: rgb(255 255 255);
        white-space: nowrap;
    }

    .dark .admin-storefront-select-wrap {
        border-color: rgb(63 63 70);
        background: rgb(24 24 27);
    }

    .admin-storefront-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 9999px;
        background: #dc2626;
        flex-shrink: 0;
    }

    .admin-storefront-select {
        min-width: 12rem;
        border: 0;
        border-radius: .5rem;
        padding: .5rem 2rem .5rem 0;
        background-color: transparent;
        color: inherit;
        font-size: .875rem;
        font-weight: 600;
        line-height: 1.25rem;
        cursor: pointer;
    }

    .admin-storefront-select:focus {
        outline: 2px solid #dc2626;
        outline-offset: 2px;
    }

    .admin-storefront-submit {
        border-radius: .5rem;
        padding: .5rem .75rem;
        background: #dc2626;
        color: white;
        font-size: .75rem;
        font-weight: 600;
    }

    @media (max-width: 900px) {
        .admin-storefront-select {
            min-width: 9rem;
            max-width: 11rem;
        }
    }

    @media (max-width: 480px) {
        .admin-storefront-switcher {
            margin-inline: 0;
        }

        .admin-storefront-dot {
            display: none;
        }

        .admin-storefront-select-wrap {
            padding-inline-start: .5rem;
        }

        .admin-storefront-select {
            min-width: 7.5rem;
            max-width: 8.5rem;
            font-size: .75rem;
        }
    }
</style>
