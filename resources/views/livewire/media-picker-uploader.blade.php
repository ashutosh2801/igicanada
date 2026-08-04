<div class="simple-media-upload">
    <style>
        .simple-media-upload-zone {
            position: relative;
            display: grid;
            min-height: 6.5rem;
            cursor: pointer;
            place-items: center;
            border: 2px dashed var(--gray-300);
            border-radius: 0.875rem;
            padding: 1rem;
            background: white;
            text-align: center;
            transition: border-color 150ms, background 150ms, transform 150ms;
        }

        .simple-media-upload-zone:hover,
        .simple-media-upload-zone:focus-within {
            border-color: var(--primary-500);
            background: var(--primary-50);
            transform: translateY(-1px);
        }

        .simple-media-upload-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            opacity: 0;
        }

        .simple-media-upload-icon {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            margin: 0 auto 0.5rem;
            place-items: center;
            border-radius: 9999px;
            background: var(--primary-50);
            color: var(--primary-600);
        }

        .simple-media-upload-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .simple-media-upload-title {
            color: var(--gray-900);
            font-size: 0.875rem;
            font-weight: 700;
        }

        .simple-media-upload-title span {
            color: var(--primary-600);
        }

        .simple-media-upload-help {
            margin-top: 0.2rem;
            color: var(--gray-500);
            font-size: 0.72rem;
        }

        .simple-media-upload-status {
            color: var(--primary-600);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .simple-media-upload-error {
            margin-top: 0.4rem;
            color: var(--danger-600);
            font-size: 0.75rem;
        }

        .dark .simple-media-upload-zone {
            border-color: color-mix(in srgb, white 18%, transparent);
            background: var(--gray-900);
        }

        .dark .simple-media-upload-zone:hover,
        .dark .simple-media-upload-zone:focus-within,
        .dark .simple-media-upload-icon {
            background: color-mix(in srgb, var(--primary-600) 15%, var(--gray-900));
        }

        .dark .simple-media-upload-title {
            color: white;
        }
    </style>

    <label class="simple-media-upload-zone">
        <input wire:model="uploads" type="file" accept="image/*" multiple class="simple-media-upload-input" />

        <span wire:loading.remove wire:target="uploads">
            <span class="simple-media-upload-icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a.75.75 0 0 1 .75.75v7.69l2.72-2.72a.75.75 0 1 1 1.06 1.06l-4 4a.75.75 0 0 1-1.06 0l-4-4a.75.75 0 0 1 1.06-1.06l2.72 2.72V2.75A.75.75 0 0 1 10 2Z"/><path d="M3.5 12.75a.75.75 0 0 1 .75.75v2h11.5v-2a.75.75 0 0 1 1.5 0v2.25A1.25 1.25 0 0 1 16 17H4a1.25 1.25 0 0 1-1.25-1.25V13.5a.75.75 0 0 1 .75-.75Z"/></svg>
            </span>
            <p class="simple-media-upload-title">Drag images here or <span>Browse</span></p>
            <p class="simple-media-upload-help">Up to 20 images · JPG, PNG, GIF or WebP · 12 MB each</p>
        </span>

        <span wire:loading wire:target="uploads" class="simple-media-upload-status">Uploading images…</span>
    </label>

    @error('uploads') <p class="simple-media-upload-error">{{ $message }}</p> @enderror
    @error('uploads.*') <p class="simple-media-upload-error">{{ $message }}</p> @enderror
</div>
