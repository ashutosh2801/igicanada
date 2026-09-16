@php
    $serverState = (string) ($state ?? '');
    $recordKey = (string) $recordKey;
@endphp

<div
    x-data="{
        recordKey: @js($recordKey),
        serverValue: @js($serverState),
        value: @js($serverState),
        init() {
            try {
                const pending = $wire?.pendingStockUpdates
                if (pending && pending[@js($recordKey)] !== undefined) {
                    this.value = pending[@js($recordKey)]
                }
            } catch (error) {}
        },
    }"
    wire:ignore.self
    class="w-16"
>
    <input
        type="number"
        x-model="value"
        x-on:change="$wire.set('pendingStockUpdates.' + recordKey, value)"
        :style="{
            border: '1px solid ' + (value !== serverValue && value !== '' ? 'var(--color-primary-500)' : '#9ca3af'),
            backgroundColor: '#ffffff',
            borderRadius: '0.375rem',
            textAlign: 'center',
            width: '3rem',
            paddingLeft: '0.25rem',
            paddingRight: '0.25rem',
        }"
        min="0"
        step="1"
        inputmode="numeric"
        class="tabular-nums"
        aria-label="Stock"
    />
</div>