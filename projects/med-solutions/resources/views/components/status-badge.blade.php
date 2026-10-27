@props([
    'status' => 'pending',
])

@php
    $normalized = is_object($status) && property_exists($status, 'value') ? $status->value : (string) $status;

    $labels = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'approved' => 'Visa Approved',
        'vil_issued' => 'VIL Issued',
        'documents_received' => 'Docs Received',
        'ivac_submitted' => 'IVAC Submitted',
        'slot_confirmed' => 'Slot Confirmed',
        'requested' => 'Requested',
        'not_requested' => 'Not Applicable',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold tracking-wide ring-1 ring-inset',
    'bg-amber-50 text-amber-900 ring-amber-600/30' => in_array($normalized, ['pending', 'requested', 'documents_received']),
    'bg-brand-50 text-brand-900 ring-brand-600/30' => in_array($normalized, ['processing', 'vil_issued', 'ivac_submitted']),
    'bg-emerald-50 text-emerald-900 ring-emerald-600/30' => in_array($normalized, ['confirmed', 'scheduled', 'completed', 'approved', 'slot_confirmed']),
    'bg-red-50 text-red-900 ring-red-600/30' => $normalized === 'cancelled',
    'bg-slate-100 text-slate-700 ring-slate-500/20' => $normalized === 'not_requested',
]) }}>
    <span @class([
        'h-1.5 w-1.5 rounded-full',
        'bg-amber-600' => in_array($normalized, ['pending', 'requested', 'documents_received']),
        'bg-brand-600' => in_array($normalized, ['processing', 'vil_issued', 'ivac_submitted']),
        'bg-emerald-600' => in_array($normalized, ['confirmed', 'scheduled', 'completed', 'approved', 'slot_confirmed']),
        'bg-red-600' => $normalized === 'cancelled',
        'bg-slate-400' => $normalized === 'not_requested',
    ]) aria-hidden="true"></span>
    {{ $labels[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized)) }}
</span>
