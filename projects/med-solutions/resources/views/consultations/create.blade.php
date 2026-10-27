@extends('layouts.app')

@section('title', 'Telemedicine Video Consultation Booking | Med Solutions')

@section('content')
<section class="py-12 sm:py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-8 text-center">
            <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">
                Pre-Travel & Post-Return Telemedicine
            </span>
            <h1 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                Book a Video Consultation with Specialist Doctors in India
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                Schedule an online video session before travelling to discuss treatment plans, or book a follow-up consultation after returning to Bangladesh.
            </p>
        </div>

        <form action="{{ route('consultations.store') }}" method="POST" enctype="multipart/form-data" class="med-card p-6 sm:p-10" novalidate>
            @csrf
            <x-form-error-summary :errors="$errors" />

            <!-- Stage Selector -->
            <fieldset class="mb-6">
                <legend class="text-sm font-bold text-slate-900">Select Consultation Type <span class="text-red-600">*</span></legend>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50/70">
                        <input type="radio" name="consultation_stage" value="pre_travel" @checked(old('consultation_stage', 'pre_travel') === 'pre_travel') class="mt-1 text-brand-800 focus:ring-brand-700">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Pre-Travel Consultation</span>
                            <span class="block text-xs text-slate-600">Review reports with an Indian specialist to plan treatment & costs before departure</span>
                        </div>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 has-[:checked]:border-brand-700 has-[:checked]:bg-brand-50/70">
                        <input type="radio" name="consultation_stage" value="post_return" @checked(old('consultation_stage') === 'post_return') class="mt-1 text-brand-800 focus:ring-brand-700">
                        <div>
                            <span class="block text-sm font-bold text-slate-900">Post-Return Follow-Up</span>
                            <span class="block text-xs text-slate-600">Review recovery progress, lab reports & medications after returning home</span>
                        </div>
                    </label>
                </div>
            </fieldset>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="field-patient_name" class="block text-sm font-semibold text-slate-800">Patient Full Name <span class="text-red-600">*</span></label>
                    <input id="field-patient_name" name="patient_name" type="text" value="{{ old('patient_name', auth()->user()?->name) }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    @error('patient_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="field-phone" class="block text-sm font-semibold text-slate-800">Mobile / WhatsApp Number <span class="text-red-600">*</span></label>
                    <input id="field-phone" name="phone" type="tel" value="{{ old('phone', auth()->user()?->phone) }}" placeholder="017XXXXXXXX" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="field-department" class="block text-sm font-semibold text-slate-800">Medical Department <span class="text-red-600">*</span></label>
                    <select id="field-department" name="department" required
                            class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected(old('department') === $dept)>{{ $dept }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="field-preferred_doctor" class="block text-sm font-semibold text-slate-800">Preferred Specialist / Hospital (Optional)</label>
                    <input id="field-preferred_doctor" name="preferred_doctor" type="text" value="{{ old('preferred_doctor') }}" placeholder="Doctor name & hospital"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                </div>

                <div>
                    <label for="field-preferred_date" class="block text-sm font-semibold text-slate-800">Preferred Date & Time <span class="text-red-600">*</span></label>
                    <input id="field-preferred_date" name="preferred_date" type="datetime-local" value="{{ old('preferred_date') }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                    @error('preferred_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="field-medical_report" class="block text-sm font-semibold text-slate-800">Attach Latest Medical Report (PDF/Image)</label>
                    <input id="field-medical_report" name="medical_report" type="file" accept=".pdf,.jpg,.jpeg,.png"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-brand-900">
                </div>

                <div class="sm:col-span-2">
                    <label for="field-symptoms_notes" class="block text-sm font-semibold text-slate-800">Symptoms Summary / Questions for the Doctor</label>
                    <textarea id="field-symptoms_notes" name="symptoms_notes" rows="3"
                              class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">{{ old('symptoms_notes') }}</textarea>
                </div>
            </div>

            <div class="mt-7 flex justify-end">
                <button type="submit" class="cursor-pointer rounded-xl btn-brand px-7 py-3.5 text-sm font-bold text-white shadow-md ">
                    Schedule Video Consultation
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
