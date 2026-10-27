<div class="med-card p-5">
    <form wire:submit="search" class="flex flex-col gap-2.5 sm:flex-row">
        <div class="w-full">
            <label for="lw-tracking" class="sr-only">Tracking Code</label>
            <input
                id="lw-tracking"
                type="text"
                wire:model.live.debounce.300ms="trackingCode"
                placeholder="Enter Tracking Code (e.g. MS-2026-DEMO1)"
                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm uppercase focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
            >
            @error('trackingCode')
                <p role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="cursor-pointer shrink-0 rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-900 disabled:opacity-60"
        >
            <span wire:loading.remove>Track Status</span>
            <span wire:loading>Searching...</span>
        </button>
    </form>

    @if ($searched && ! $foundRequest)
        <p role="alert" class="mt-3 text-xs font-medium text-amber-800">No service request found for that tracking code.</p>
    @endif

    @if ($foundRequest)
        <div class="mt-4 rounded-xl bg-brand-50/70 p-4 text-sm">
            <div class="flex items-center justify-between">
                <span class="font-mono font-bold text-brand-950">{{ $foundRequest->tracking_code }}</span>
                <x-status-badge :status="$foundRequest->status" />
            </div>
            <p class="mt-1 text-xs text-slate-700">Patient: {{ $foundRequest->patient_name }} • Hospital: {{ $foundRequest->preferred_hospital ?? 'Under Review' }}</p>
        </div>
    @endif
</div>
