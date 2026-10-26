@props([
    'slug' => 'appointment',
    'title' => '',
    'subtitle' => '',
    'description' => '',
    'icon' => 'calendar-check',
    'number' => '01',
])

<article class="med-card group relative flex flex-col justify-between p-6">
    <div>
        <div class="mb-5 flex items-center justify-between">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200 transition-colors duration-200 group-hover:bg-cyan-700 group-hover:text-white">
                @switch($icon)
                    @case('calendar-check')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
                            <path d="m9 16 2 2 4-4" />
                        </svg>
                        @break
                    @case('passport')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="2" width="16" height="20" rx="2" />
                            <circle cx="12" cy="10" r="3" />
                            <path d="M8 17h8" />
                        </svg>
                        @break
                    @case('video')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="2" y="6" width="14" height="12" rx="2" />
                            <path d="m22 8-6 4 6 4V8Z" />
                        </svg>
                        @break
                    @case('clipboard-pulse')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M9 2h6v4H9z" />
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                            <path d="M8 14h2l1.5-3 2 6 1.5-3H17" />
                        </svg>
                        @break
                    @case('car')
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2" />
                            <circle cx="6.5" cy="16.5" r="2.5" /><circle cx="16.5" cy="16.5" r="2.5" />
                        </svg>
                        @break
                    @default
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                @endswitch
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                Service #{{ $number }}
            </span>
        </div>

        <h3 class="font-heading text-xl font-bold text-slate-900">
            {{ $title }}
        </h3>
        @if($subtitle)
            <p class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-cyan-800">
                {{ $subtitle }}
            </p>
        @endif

        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            {{ $description }}
        </p>
    </div>

    <div class="mt-6 border-t border-slate-100 pt-4">
        <a
            href="{{ $slug === 'telemedicine' ? route('consultations.create') : route('services.create', ['service' => $slug]) }}"
            class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-cyan-800 transition-colors duration-200 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-700 focus-visible:ring-offset-2 rounded"
        >
            <span>Request This Service</span>
            <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
            </svg>
        </a>
    </div>
</article>
