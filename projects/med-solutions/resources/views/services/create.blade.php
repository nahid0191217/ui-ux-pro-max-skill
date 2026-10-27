@extends('layouts.app')

@section('title', 'Service Request & Booking Form | Med Solutions')

@section('content')
<section class="py-12 sm:py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">
                Online Patient Assistance Booking
            </span>
            <h1 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                Medical Assistance & Appointment Request Form
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                Submit your patient details and medical reports below. You will receive an instant <strong>Tracking Code</strong> to monitor your request status online.
            </p>
        </div>

        <form
            action="{{ route('services.store') }}"
            method="POST"
            enctype="multipart/form-data"
            x-data="{ fileName: '' }"
            class="med-card p-6 sm:p-10"
            novalidate
        >
            @csrf

            <x-form-error-summary :errors="$errors" />

            <!-- Step 1: Patient Details -->
            <div class="border-b border-slate-100 pb-7">
                <h2 class="font-heading text-lg font-bold text-slate-900">1. Patient Personal & Contact Information</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="field-patient_name" class="block text-sm font-semibold text-slate-800">
                            Patient Full Name (As on Passport) <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="field-patient_name"
                            name="patient_name"
                            type="text"
                            required
                            value="{{ old('patient_name', auth()->user()?->name) }}"
                            placeholder="e.g. Md. Kamrul Islam"
                            @error('patient_name') aria-invalid="true" aria-describedby="error-patient_name" @enderror
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                        >
                        @error('patient_name')
                            <p id="error-patient_name" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="field-phone" class="block text-sm font-semibold text-slate-800">
                            Mobile / WhatsApp Number <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="field-phone"
                            name="phone"
                            type="tel"
                            required
                            value="{{ old('phone', auth()->user()?->phone) }}"
                            placeholder="017XXXXXXXX"
                            @error('phone') aria-invalid="true" aria-describedby="error-phone" @enderror
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                        >
                        @error('phone')
                            <p id="error-phone" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="field-email" class="block text-sm font-semibold text-slate-800">
                            Email Address (For VIL & Appointment Confirmation)
                        </label>
                        <input
                            id="field-email"
                            name="email"
                            type="email"
                            value="{{ old('email', auth()->user()?->email) }}"
                            placeholder="patient@example.com"
                            @error('email') aria-invalid="true" aria-describedby="error-email" @enderror
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                        >
                        @error('email')
                            <p id="error-email" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Step 2: Multi-Select Assistance Services -->
            <fieldset id="field-selected_services" class="mt-7 border-b border-slate-100 pb-7">
                <legend class="font-heading text-lg font-bold text-slate-900">
                    2. Select Required Assistance Services <span class="text-red-600">*</span>
                </legend>
                <p class="mt-1 text-xs text-slate-500">You may select multiple services for comprehensive support.</p>

                @php
                    $checkedServices = old('selected_services', [$preselectedService]);
                @endphp

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($serviceLabels as $value => $label)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3.5 transition hover:border-accent-500 hover:bg-brand-50/40 has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50/70">
                            <input
                                type="checkbox"
                                name="selected_services[]"
                                value="{{ $value }}"
                                @checked(in_array($value, (array) $checkedServices, true))
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-800 focus:ring-brand-700"
                            >
                            <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('selected_services')
                    <p class="mt-2 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </fieldset>

            <!-- Step 3: Preferred Hospital, Doctor & Medical Report Upload -->
            <div class="mt-7 space-y-5">
                <h2 class="font-heading text-lg font-bold text-slate-900">3. Preferred Hospital, Specialist & Medical Reports</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="field-preferred_hospital" class="block text-sm font-semibold text-slate-800">
                            Preferred Hospital in India (Optional)
                        </label>
                        <select
                            id="field-preferred_hospital"
                            name="preferred_hospital"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                        >
                            <option value="">Recommend the Best Hospital Based on My Reports</option>
                            @foreach ($partnerHospitals as $hospital)
                                <option value="{{ $hospital }}" @selected(old('preferred_hospital') === $hospital)>
                                    {{ $hospital }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="field-preferred_doctor" class="block text-sm font-semibold text-slate-800">
                            Preferred Specialist Doctor / Department
                        </label>
                        <input
                            id="field-preferred_doctor"
                            name="preferred_doctor"
                            type="text"
                            value="{{ old('preferred_doctor') }}"
                            placeholder="e.g. Dr. Devi Shetty / Cardiology / Oncology"
                            class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                        >
                    </div>
                </div>

                <!-- Secure Medical Report File Upload -->
                <div>
                    <label for="field-medical_report" class="block text-sm font-semibold text-slate-800">
                        Upload Medical Reports & Prescriptions (PDF, JPG, PNG — Max 10 MB)
                    </label>
                    <label
                        for="field-medical_report"
                        class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-accent-300 bg-brand-50/40 px-6 py-7 text-center transition hover:border-brand-600 hover:bg-brand-50"
                    >
                        <svg class="h-9 w-9 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span class="mt-2 text-sm font-semibold text-slate-800" x-text="fileName || 'Click here to select a medical report file'">
                            Click here to select a medical report file
                        </span>
                        <span class="mt-1 text-xs text-slate-500">
                            Stored securely in encrypted private storage accessible only to authorized coordinators
                        </span>
                        <input
                            id="field-medical_report"
                            name="medical_report"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                            @change="fileName = $event.target.files[0]?.name || ''"
                            class="sr-only"
                        >
                    </label>
                    @error('medical_report')
                        <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-details" class="block text-sm font-semibold text-slate-800">
                        Clinical Condition & Special Requirements (Optional)
                    </label>
                    <textarea
                        id="field-details"
                        name="details"
                        rows="4"
                        placeholder="Share patient age, current symptoms, number of accompanying attendants, wheelchair pickup needs, or kitchen accommodation preferences..."
                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                    >{{ old('details') }}</textarea>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-6">
                <p class="text-xs text-slate-500">
                    Our dedicated medical care coordinator will contact you promptly upon submission.
                </p>
                <button
                    type="submit"
                    class="cursor-pointer inline-flex items-center gap-2 rounded-xl btn-brand px-7 py-3.5 text-sm font-bold text-white shadow-md transition  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
                >
                    <span>Submit Service Request</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
