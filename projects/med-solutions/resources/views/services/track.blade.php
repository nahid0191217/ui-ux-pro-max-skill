@extends('layouts.app')

@section('title', 'Track Service Request Status | Med Solutions')

@section('content')
<section class="py-12 sm:py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="med-card p-6 sm:p-8">
            <h1 class="font-heading text-2xl font-extrabold text-slate-900">Track Live Request Status</h1>
            <p class="mt-1 text-sm text-slate-600">
                For patient privacy, please enter both your <strong>Tracking Code</strong> and your <strong>Registered Phone Number</strong> (or sign in to your account).
            </p>

            <form action="{{ route('services.track') }}" method="GET" class="mt-5 grid gap-3 sm:grid-cols-12">
                <div class="sm:col-span-5">
                    <label for="track-code-input" class="sr-only">Tracking Code</label>
                    <input
                        id="track-code-input"
                        type="text"
                        name="tracking_code"
                        value="{{ $trackingCode }}"
                        placeholder="Tracking Code (e.g. MS-2026-XXXXX)"
                        required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium uppercase focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20"
                    >
                </div>
                <div class="sm:col-span-5">
                    <label for="track-phone-input" class="sr-only">Registered Phone Number</label>
                    <input
                        id="track-phone-input"
                        type="tel"
                        name="phone"
                        value="{{ $phoneInput ?? '' }}"
                        placeholder="Registered Phone Number"
                        @guest required @endguest
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20"
                    >
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full cursor-pointer rounded-xl bg-cyan-800 px-5 py-3 text-sm font-bold text-white hover:bg-cyan-900">
                        Verify
                    </button>
                </div>
            </form>

            @if ($trackingCode !== '' && ! $serviceRequest)
                <div role="alert" class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                    @if (!empty($verificationError))
                        Please enter the registered phone number matching tracking code <strong>{{ $trackingCode }}</strong>, or <a href="{{ route('login') }}" class="font-bold underline">sign in</a> to view your request.
                    @else
                        No service request was found matching tracking code <strong>{{ $trackingCode }}</strong>. Please verify your code and try again.
                    @endif
                </div>
            @endif

            @if ($serviceRequest)
                @php
                    $stages = ['pending' => 1, 'processing' => 2, 'confirmed' => 3, 'completed' => 4];
                    $currentStep = $stages[$serviceRequest->status->value] ?? 1;
                @endphp

                <div class="mt-8 border-t border-slate-100 pt-7">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tracking Code</span>
                            <h2 class="font-heading text-xl font-extrabold text-cyan-900">{{ $serviceRequest->tracking_code }}</h2>
                            <p class="text-sm text-slate-600">Patient: <strong>{{ $serviceRequest->patient_name }}</strong></p>
                        </div>
                        <x-status-badge :status="$serviceRequest->status" />
                    </div>

                    <!-- 4-Stage Visual Progress Stepper -->
                    <ol class="mt-7 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([1 => 'Pending', 2 => 'Processing', 3 => 'Confirmed', 4 => 'Completed'] as $stepNum => $stepLabel)
                            <li @class([
                                'rounded-xl border p-3.5 text-center text-xs font-bold',
                                'border-emerald-600 bg-emerald-50 text-emerald-950' => $currentStep >= $stepNum,
                                'border-slate-200 bg-slate-50 text-slate-400' => $currentStep < $stepNum,
                            ])>
                                <span class="block text-[11px] uppercase">Step {{ $stepNum }}</span>
                                <span class="mt-1 block">{{ $stepLabel }}</span>
                            </li>
                        @endforeach
                    </ol>

                    <dl class="mt-7 grid gap-4 rounded-xl bg-slate-50 p-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Preferred Hospital & Specialist</dt>
                            <dd class="mt-1 font-semibold text-slate-900">
                                {{ $serviceRequest->preferred_hospital ?? 'Under Specialist Review' }}
                                @if($serviceRequest->preferred_doctor) — {{ $serviceRequest->preferred_doctor }} @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-500">Visa & Appointment Progress</dt>
                            <dd class="mt-1 flex flex-wrap gap-2">
                                <x-status-badge :status="$serviceRequest->visa_status" />
                                <x-status-badge :status="$serviceRequest->appointment_status" />
                            </dd>
                        </div>
                        @if($serviceRequest->admin_notes)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-semibold text-slate-500">Care Coordinator Update</dt>
                                <dd class="mt-1 text-slate-800">{{ $serviceRequest->admin_notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
