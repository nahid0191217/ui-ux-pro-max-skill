@props(['errors'])

@if ($errors->any())
    <div
        role="alert"
        tabindex="-1"
        aria-labelledby="form-error-summary-heading"
        class="mb-6 rounded-xl border border-red-300 bg-red-50 p-4 text-red-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-red-600"
    >
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <div>
                <h2 id="form-error-summary-heading" class="text-sm font-bold">
                    Please review the following {{ $errors->count() === 1 ? 'issue' : $errors->count().' issues' }}:
                </h2>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->messages() as $field => $messages)
                        <li>
                            <a href="#field-{{ $field }}" class="underline hover:text-red-950 focus:outline-none focus:ring-2 focus:ring-red-700">
                                {{ $messages[0] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
