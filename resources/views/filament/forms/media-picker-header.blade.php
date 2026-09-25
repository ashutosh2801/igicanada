<div x-on:media-picker-uploaded.window="$wire.$refresh()">
    <livewire:media-picker-uploader />
</div>

@php
    $selectedIds = method_exists($this, 'selectedMediaAssetIds') ? $this->selectedMediaAssetIds() : [];
    $selectedAssets = $selectedIds
        ? \App\Models\MediaAsset::query()->whereIn('id', $selectedIds)->get()
        : collect();
@endphp

@if ($selectedAssets->isNotEmpty())
    <div class="media-picker-selected">
        <div class="media-picker-selected-title">
            Selected ({{ $selectedAssets->count() }})
            <span class="media-picker-selected-hint">Click &times; to remove an image you picked by mistake.</span>
        </div>
        <div class="media-picker-selected-grid">
            @foreach ($selectedAssets as $asset)
                <div class="media-picker-selected-item">
                    <img src="{{ e($asset->url()) }}" alt="{{ e($asset->display_name) }}" title="{{ e($asset->display_name) }}" />
                    <div class="media-picker-selected-meta">
                        <span class="media-picker-selected-name" title="{{ e($asset->display_name) }}">{{ $asset->display_name }}</span>
                        <button
                            type="button"
                            class="media-picker-selected-remove"
                            wire:click="removeSelectedImage({{ $asset->id }})"
                            title="Remove {{ e($asset->display_name) }}"
                            aria-label="Remove {{ e($asset->display_name) }}"
                        >&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif