<div x-on:media-picker-uploaded.window="$wire.$refresh()">
    <livewire:media-picker-uploader />
</div>

@php
    $selectedIds = method_exists($this, 'selectedMediaAssetIds') ? $this->selectedMediaAssetIds() : [];
    $selectedAssets = $selectedIds
        ? \App\Models\MediaAsset::query()->whereIn('id', $selectedIds)->get()
        : collect();
    $assetMap = $selectedAssets
        ->mapWithKeys(fn (\App\Models\MediaAsset $asset): array => [(string) $asset->id => [
            'url' => $asset->url(),
            'name' => $asset->display_name,
        ]])
        ->all();
@endphp

@if ($selectedAssets->isNotEmpty())
    <div
        class="media-picker-selected"
        x-data="{
            dragging: null,
            ids: {{ Js::from(array_values($selectedIds)) }},
            assets: {{ Js::from($assetMap) }},
            commit() {
                $wire.reorderSelectedImages(this.ids);
            },
            removeAt(index) {
                const [id] = this.ids.splice(index, 1);
                if (id !== undefined) {
                    $wire.removeSelectedImage(id);
                }
            },
        }"
    >
        <div class="media-picker-selected-title">
            Selected (<span x-text="ids.length"></span>)
            <span class="media-picker-selected-hint">Drag the grip to set display order &middot; Click &times; to remove.</span>
        </div>
        <div class="media-picker-selected-grid" @dragover.prevent @drop.prevent="dragging = null">
            <template x-for="(id, index) in ids" :key="id">
                <div
                    class="media-picker-selected-item"
                    :class="{ 'is-dragging': dragging === index }"
                    draggable="true"
                    @dragstart="dragging = index"
                    @dragenter.prevent="if (dragging !== null && dragging !== index) { const moved = ids.splice(dragging, 1)[0]; ids.splice(index, 0, moved); dragging = index; }"
                    @dragend="dragging = null; commit()"
                >
                    <span class="media-picker-selected-grip" aria-hidden="true" title="Drag to reorder">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20" width="14" height="14">
                            <circle cx="7" cy="5" r="1.3"></circle>
                            <circle cx="13" cy="5" r="1.3"></circle>
                            <circle cx="7" cy="10" r="1.3"></circle>
                            <circle cx="13" cy="10" r="1.3"></circle>
                            <circle cx="7" cy="15" r="1.3"></circle>
                            <circle cx="13" cy="15" r="1.3"></circle>
                        </svg>
                    </span>
                    <img :src="assets[id].url" :alt="assets[id].name" :title="assets[id].name" />
                    <div class="media-picker-selected-meta">
                        <span class="media-picker-selected-name" :title="assets[id].name" x-text="assets[id].name"></span>
                        <button
                            type="button"
                            class="media-picker-selected-remove"
                            :title="'Remove ' + assets[id].name"
                            aria-label="Remove image"
                            @click.stop.prevent="removeAt(index)"
                        >&times;</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
@endif