@php
    $user = auth()->user();
@endphp

<x-layouts.sourdough>
    <div class="px-4 py-6">
        <livewire:notifications-manager />
    </div>
</x-layouts.sourdough>