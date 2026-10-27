@extends('layouts.app')

@section('title', 'Med Solutions | Your Right Decision Ensures Your Best Medical Treatment')

@section('content')
    <!-- 1. HERO SECTION -->
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-50 via-white to-emerald-50/60 py-14 sm:py-20 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <div class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-4 py-1.5 text-xs font-bold text-brand-900 shadow-xs">
                        <span class="h-2 w-2 rounded-full bg-emerald-600" aria-hidden="true"></span>
                        <span>Bangladesh’s Trusted Partner for Medical Treatment in India</span>
                    </div>

                    <h1 class="mt-6 font-heading text-3xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Your Right Decision Ensures <span class="text-brand-800">Your Best Medical Treatment</span>
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg">
                        <strong>Med Solutions</strong> manages every step of your healthcare journey from Bangladesh to India—from reviewing medical reports and booking top specialist appointments to issuing official Visa Invitation Letters (VIL), telemedicine video consults, airport pickup, and affordable self-cooking accommodation beside the hospital.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ route('services.create') }}" class="cursor-pointer inline-flex items-center gap-2.5 rounded-xl btn-brand px-6 py-3.5 text-base font-bold text-white shadow-md transition duration-200  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
                            <span>Get Free Treatment Plan & Appointment</span>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                        <a href="{{ route('consultations.create') }}" class="cursor-pointer inline-flex items-center gap-2.5 rounded-xl border-2 border-brand-800 bg-white px-6 py-3.5 text-base font-bold text-brand-900 transition duration-200 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                            <svg class="h-5 w-5 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4V8Z"/>
                            </svg>
                            <span>Book Video Consultation</span>
                        </a>
                    </div>

                    <!-- Instant Status Tracking Bar -->
                    <form action="{{ route('services.track') }}" method="GET" class="mt-8 max-w-xl rounded-2xl border border-brand-100 bg-white p-2 shadow-sm">
                        <label for="hero-tracking-input" class="sr-only">Track Request Status by Tracking Code</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input
                                id="hero-tracking-input"
                                name="tracking_code"
                                type="text"
                                placeholder="Enter Tracking Code (e.g. MS-2026-XXXXX)"
                                class="w-full rounded-xl border-0 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-700"
                            >
                            <button type="submit" class="cursor-pointer shrink-0 rounded-xl bg-brand-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900">
                                Track Status
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Trust & Quick Highlights Card -->
                <div class="lg:col-span-5">
                    <div class="med-card relative overflow-hidden p-6 sm:p-8">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-brand-800">End-to-End Patient Care</p>
                                <h2 class="mt-1 font-heading text-xl font-bold text-slate-900">Why Choose Med Solutions?</h2>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>
                                </svg>
                            </div>
                        </div>

                        <dl class="mt-6 grid grid-cols-2 gap-4">
                            <div class="rounded-xl bg-brand-50/70 p-4">
                                <dt class="text-xs font-medium text-slate-600">Partner Hospitals</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-brand-900">50+ Accredited</dd>
                            </div>
                            <div class="rounded-xl bg-emerald-50/70 p-4">
                                <dt class="text-xs font-medium text-slate-600">Patients Assisted</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-emerald-900">10,000+</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-600">VIL & Visa Turnaround</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-slate-900">24–48 Hours</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <dt class="text-xs font-medium text-slate-600">Language Interpreters</dt>
                                <dd class="mt-1 font-heading text-2xl font-extrabold text-slate-900">100% Dedicated</dd>
                            </div>
                        </dl>

                        <div class="mt-6 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Leading Indian Hospital Network</p>
                            <p class="mt-1.5 text-sm font-semibold text-slate-800">
                                Apollo • CMC Vellore • Manipal • Narayana Health • Fortis • Medanta • AIG • Tata Memorial
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. ABOUT US SECTION -->
    <section id="about" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-800">About Med Solutions</span>
                    <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                        Making Cross-Border Healthcare Safe, Transparent & Stress-Free for Bangladeshi Patients
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-slate-600">
                        Without verified guidance, patients travelling abroad often face wrong department referrals, unexpected hospital costs, language barriers, and accommodation struggles. <strong>Med Solutions</strong> serves as your dedicated cross-border care organization—supporting you before departure, throughout hospital admission in India, and after you return home.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm font-medium text-slate-700">
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Zero hidden middleman fees — pay all medical bills directly at the official hospital counter.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Pre-travel specialist video consultations and post-return follow-up care from Bangladesh.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Airport/station pickup, Bengali-speaking interpreters, and affordable self-cooking stays.</span>
                        </li>
                    </ul>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Right Specialist Match</h3>
                        <p class="mt-2 text-sm text-slate-600">Clinical review of your reports to match you with the most experienced senior consultant in the exact sub-specialty.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Fast Visa Invitation (VIL)</h3>
                        <p class="mt-2 text-sm text-slate-600">Official Visa Invitation Letters issued directly by Indian hospitals in High Commission–approved format.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Upfront Cost Transparency</h3>
                        <p class="mt-2 text-sm text-slate-600">Written pre-travel estimates covering recommended procedures, length of stay, and expected expenses.</p>
                    </div>
                    <div class="med-card p-5">
                        <h3 class="font-heading font-bold text-brand-900">Continuity of Follow-Up</h3>
                        <p class="mt-2 text-sm text-slate-600">Structured telemedicine video follow-ups with your treating doctor once you return home to Bangladesh.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. SERVICES SHOWCASE GRID (6 CORE SERVICES) -->
    <section id="services" class="bg-slate-50 py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Our Core Services</span>
                <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-4xl">
                    Complete End-to-End Medical Tourism Assistance
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    Select one or multiple assistance services tailored to your treatment needs and submit your request online.
                </p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $index => $service)
                    <x-service-card
                        :slug="$service->slug"
                        :title="$service->title"
                        :subtitle="$service->subtitle"
                        :description="$service->description"
                        :icon="$service->icon_name"
                        :number="sprintf('%02d', $index + 1)"
                    />
                @endforeach
            </div>
        </div>
    </section>

    <!-- 4. STEP-BY-STEP PRE-TRAVEL GUIDE -->
    <section id="pre-travel-guide" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-block rounded-full bg-brand-50 px-3.5 py-1 text-xs font-bold text-brand-900 ring-1 ring-brand-200">
                    Patient & Attendant Preparation Guide
                </span>
                <h2 class="mt-3 font-heading text-2xl font-extrabold text-slate-900 sm:text-4xl">
                    Essential Information Before Travelling to India for Medical Treatment
                </h2>
                <p class="mt-3 text-base text-slate-600">
                    Follow these 5 essential steps before departure to ensure smooth visa approval, immigration clearance, and hospital admission.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-5">
                @foreach ($preTravelSteps as $item)
                    <div class="med-card relative flex flex-col p-6">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-800 font-heading text-base font-extrabold text-white">
                            {{ $item['step'] }}
                        </span>
                        <h3 class="mt-4 font-heading text-lg font-bold text-slate-900">
                            {{ $item['title'] }}
                        </h3>
                        <p class="mt-2.5 text-sm leading-relaxed text-slate-600">
                            {{ $item['desc'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            <!-- Essential Pre-Travel Checklist Callout -->
            <div class="mt-10 rounded-2xl border border-brand-200 bg-brand-50/60 p-6 sm:p-8">
                <div class="grid items-center gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <h3 class="font-heading text-lg font-bold text-slate-900">Mandatory Pre-Travel Document Checklist:</h3>
                        <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                            <p>• Valid Passport (minimum 6 months validity) + previous passports</p>
                            <p>• Official Hospital Visa Invitation Letter (VIL) addressed to High Commission</p>
                            <p>• Local doctor referral summary & chronological diagnostic reports</p>
                            <p>• Bank solvency / USD endorsement on passport & National ID (NID)</p>
                        </div>
                    </div>
                    <div class="lg:text-right">
                        <a href="{{ route('services.create', ['service' => 'visa_processing']) }}" class="cursor-pointer inline-flex items-center justify-center rounded-xl bg-brand-800 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900">
                            Request Visa & VIL Assistance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. ACCESSIBLE TESTIMONIALS CAROUSEL -->
    <section class="bg-slate-50 py-16 sm:py-20" aria-labelledby="testimonials-heading">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
             x-data="{
                 active: 0,
                 paused: false,
                 total: {{ max($testimonials->count(), 1) }},
                 next() { this.active = (this.active + 1) % this.total },
                 prev() { this.active = (this.active - 1 + this.total) % this.total }
             }"
        >
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Verified Patient Stories</span>
                    <h2 id="testimonials-heading" class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                        Trusted by Patients & Families Across Bangladesh
                    </h2>
                </div>

                <!-- Accessible Previous / Next Controls -->
                <div class="flex items-center gap-2">
                    <button type="button" @click="prev()" aria-label="Previous testimonial" class="cursor-pointer rounded-xl border border-slate-300 bg-white p-2.5 text-slate-700 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" @click="next()" aria-label="Next testimonial" class="cursor-pointer rounded-xl border border-slate-300 bg-white p-2.5 text-slate-700 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-3" aria-live="polite">
                @foreach ($testimonials as $testimonial)
                    <blockquote class="med-card flex flex-col justify-between p-6">
                        <div>
                            <div class="flex items-center gap-1 text-amber-500" aria-label="{{ $testimonial->rating }} out of 5 stars">
                                @for ($i = 0; $i < $testimonial->rating; $i++)
                                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endfor
                            </div>
                            <p class="mt-4 text-sm leading-relaxed text-slate-700">
                                “{{ $testimonial->quote }}”
                            </p>
                        </div>
                        <footer class="mt-6 border-t border-slate-100 pt-4">
                            <div class="font-bold text-slate-900">{{ $testimonial->patient_name }}</div>
                            <div class="text-xs text-slate-500">{{ $testimonial->district }} • {{ $testimonial->hospital_treated }}</div>
                        </footer>
                    </blockquote>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 6. CONTACT FORM SECTION -->
    <section id="contact" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <span class="text-xs font-bold uppercase tracking-widest text-brand-800">Speak With a Care Coordinator</span>
                    <h2 class="mt-2 font-heading text-2xl font-extrabold text-slate-900 sm:text-3xl">
                        Have Questions About Treatment in India? Contact Us
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">
                        Send us a message for doctor recommendations, medical visa queries, or estimated treatment budgets, or reach our 24/7 helpline directly.
                    </p>

                    <div class="mt-8 space-y-4 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-bold text-slate-900">24/7 Helpline & WhatsApp</div>
                            <div class="mt-1 text-brand-800 font-semibold">+880 XXXX-XXXXXX</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="font-bold text-slate-900">Support Email & Office Hours</div>
                            <div class="mt-1 text-slate-600">support@example.com • Office hours: to be updated</div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <form action="{{ route('contact.store') }}" method="POST" class="med-card p-6 sm:p-8" novalidate>
                        @csrf
                        <x-form-error-summary :errors="$errors" />

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="field-name" class="block text-sm font-semibold text-slate-800">Full Name <span class="text-red-600">*</span></label>
                                <input id="field-name" name="name" type="text" value="{{ old('name') }}" required
                                       @error('name') aria-invalid="true" aria-describedby="err-name" @enderror
                                       class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                                @error('name') <p id="err-name" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="field-phone" class="block text-sm font-semibold text-slate-800">Phone / WhatsApp Number <span class="text-red-600">*</span></label>
                                <input id="field-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="017XXXXXXXX" required
                                       @error('phone') aria-invalid="true" aria-describedby="err-phone" @enderror
                                       class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                                @error('phone') <p id="err-phone" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="field-email" class="block text-sm font-semibold text-slate-800">Email Address (Optional)</label>
                                <input id="field-email" name="email" type="email" value="{{ old('email') }}"
                                       class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                            </div>

                            <div>
                                <label for="field-subject" class="block text-sm font-semibold text-slate-800">Subject</label>
                                <input id="field-subject" name="subject" type="text" value="{{ old('subject') }}" placeholder="Doctor Appointment / Medical Visa Inquiry"
                                       class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="field-message" class="block text-sm font-semibold text-slate-800">Your Message or Medical Summary <span class="text-red-600">*</span></label>
                                <textarea id="field-message" name="message" rows="4" required
                                          @error('message') aria-invalid="true" aria-describedby="err-message" @enderror
                                          class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20">{{ old('message') }}</textarea>
                                @error('message') <p id="err-message" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="cursor-pointer inline-flex items-center justify-center rounded-xl bg-brand-800 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
