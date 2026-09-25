<style>
    .primary-image-thumbnail-select > div:first-child {
        overflow: hidden;
        border-radius: 0.5rem;
    }

    .media-badge-name {
        display: block;
        width: 5.5rem;
        margin-top: 0.15rem;
        overflow: hidden;
        font-size: 0.6rem;
        font-weight: 600;
        line-height: 1.1;
        text-align: center;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--gray-500);
    }

    .product-images-thumbnail-select .fi-fo-modal-table-select-badges-ctn {
        align-items: center;
        gap: 0.5rem;
    }

    .product-images-thumbnail-select .fi-badge,
    .product-images-thumbnail-select .fi-badge-label-ctn,
    .product-images-thumbnail-select .fi-badge-label {
        display: block;
        overflow: hidden;
        padding: 0 !important;
        border-radius: 0.5rem;
        background: transparent !important;
    }

    .primary-image-thumbnail-select,
    .product-images-thumbnail-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--gray-200);
        border-radius: 0.75rem;
        background: #ffffff;
    }

    .dark .primary-image-thumbnail-select,
    .dark .product-images-thumbnail-select {
        border-color: color-mix(in srgb, white 10%, var(--gray-900));
        background: color-mix(in srgb, white 4%, var(--gray-900));
    }

    .media-library-popup {
        height: min(92vh, 64rem);
        max-height: calc(100vh - 2rem);
        overflow: hidden;
        border-radius: 1rem !important;
    }

    .media-library-popup > .fi-modal-header {
        flex: none;
        gap: 0.875rem !important;
        padding: 1rem 1.5rem !important;
        background: linear-gradient(135deg, white, var(--gray-50)) !important;
    }

    .media-library-popup > .fi-modal-header .fi-modal-icon-bg {
        border-radius: 0.75rem;
        background: var(--primary-50);
    }

    .media-library-popup > .fi-modal-header .fi-modal-heading {
        font-size: 1.1rem;
        font-weight: 750;
    }

    .media-library-popup > .fi-modal-header .fi-modal-description {
        margin-top: 0.2rem;
        font-size: 0.78rem;
    }

    .media-library-popup > .fi-modal-content {
        min-height: 0;
        flex: 1;
        overflow-y: auto;
        overscroll-behavior: contain;
        gap: 1rem !important;
        padding: 1rem 1.25rem 1.25rem !important;
        background: var(--gray-50);
    }

    .media-library-popup > .fi-modal-footer {
        z-index: 20 !important;
        flex: none;
        padding: 0.8rem 1.5rem !important;
        box-shadow: 0 -8px 24px rgb(0 0 0 / 0.06);
    }

    .media-library-popup > .fi-modal-footer .fi-modal-footer-actions {
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .media-library-popup .fi-ta-ctn {
        overflow: hidden;
        border: 1px solid var(--gray-200);
        border-radius: 0.875rem;
        box-shadow: 0 1px 3px rgb(0 0 0 / 0.05);
    }

    .media-library-popup .fi-ta-header {
        padding: 1rem;
        background: white;
    }

    .media-library-popup .fi-ta-header-toolbar {
        gap: 0.75rem;
    }

    .media-library-popup .fi-ta-content-ctn {
        background: white;
    }

    @media (min-width: 80rem) {
        .media-library-popup .fi-ta-content.fi-ta-content-grid {
            grid-template-columns: repeat(6, minmax(0, 11rem)) !important;
            justify-content: center;
        }
    }

    .media-picker-record {
        display: grid !important;
        grid-template-columns: 2rem minmax(0, 1fr);
        align-items: end !important;
        padding-inline-start: 0 !important;
    }

    .media-picker-record .fi-ta-record-content-ctn {
        grid-column: 1 / -1;
        grid-row: 1;
    }

    .media-picker-record .fi-ta-record-content {
        padding-inline: 0 !important;
    }

    .media-picker-record .fi-ta-image {
        justify-content: center !important;
        padding-inline: 0 !important;
    }

    .media-picker-record .fi-ta-record-checkbox {
        z-index: 2;
        grid-column: 1;
        grid-row: 1;
        align-self: start;
        margin: 0.75rem 0 0 0.75rem !important;
    }

    .media-picker-record .media-picker-name {
        min-height: 1.5rem;
        width: 100%;
        padding-inline: 0.75rem;
        text-align: start;
    }

    .dark .media-library-popup > .fi-modal-header,
    .dark .media-library-popup > .fi-modal-content,
    .dark .media-library-popup .fi-ta-header,
    .dark .media-library-popup .fi-ta-content-ctn {
        background: var(--gray-900) !important;
    }

    .dark .media-library-popup > .fi-modal-header .fi-modal-icon-bg {
        background: color-mix(in srgb, white 7%, var(--gray-900)) !important;
    }

    .dark .media-library-popup .fi-ta-ctn {
        border-color: color-mix(in srgb, white 10%, transparent) !important;
    }

    .media-picker-selected {
        display: flex;
        align-items: stretch;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .media-picker-selected-title {
        flex: none;
        align-self: center;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--gray-700);
    }

    .dark .media-picker-selected-title {
        color: var(--gray-200);
    }

    .media-picker-selected-hint {
        display: block;
        margin-top: 0.1rem;
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--gray-400);
    }

    .media-picker-selected-grid {
        display: grid;
        flex: 1;
        grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr));
        gap: 0.5rem;
    }

    .media-picker-selected-item {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--gray-200);
        border-radius: 0.625rem;
        background: white;
    }

    .dark .media-picker-selected-item {
        border-color: color-mix(in srgb, white 10%, transparent);
        background: color-mix(in srgb, white 4%, var(--gray-900));
    }

    .media-picker-selected-item > img {
        display: block;
        width: 100%;
        height: 4.5rem;
        object-fit: cover;
    }

    .media-picker-selected-meta {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.3rem 0.4rem;
    }

    .media-picker-selected-name {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        font-size: 0.65rem;
        font-weight: 600;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--gray-600);
    }

    .media-picker-selected-remove {
        display: inline-flex;
        flex: none;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 9999px;
        font-size: 0.85rem;
        line-height: 1;
        color: var(--gray-500);
        background: var(--gray-100);
        cursor: pointer;
    }

    .media-picker-selected-remove:hover {
        color: white;
        background: var(--danger-500);
    }

    .dark .media-picker-selected-remove {
        background: color-mix(in srgb, white 10%, var(--gray-900));
    }

    @media (max-width: 47.999rem) {
        .media-library-popup {
            height: 96vh;
            max-height: 96vh;
            border-radius: 0.75rem !important;
        }

        .media-library-popup > .fi-modal-header,
        .media-library-popup > .fi-modal-footer {
            padding-inline: 1rem !important;
        }

        .media-library-popup > .fi-modal-content {
            padding: 0.75rem !important;
        }
    }
</style>
