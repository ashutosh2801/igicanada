<style>
    .admin-storefront-switcher {
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-inline: .5rem .25rem;
    }

    .admin-storefront-switcher-label {
        font-size: .75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: inherit;
        opacity: .65;
        white-space: nowrap;
    }

    .admin-storefront-indicator {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid rgb(212 212 216);
        border-radius: .5rem;
        padding: .5rem .75rem;
        font-size: .875rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .admin-storefront-dot {
        width: .5rem;
        height: .5rem;
        border-radius: 9999px;
        background: #dc2626;
        flex-shrink: 0;
    }

    @media (max-width: 640px) {
        .admin-storefront-switcher-label {
            display: none;
        }

        .admin-storefront-indicator {
            max-width: 9rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
</style>
