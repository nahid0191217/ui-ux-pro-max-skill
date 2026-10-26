@extends('layouts.app')

@section('title', 'Create Patient Account | Med Solutions')

@section('content')
<section class="py-12 sm:py-16">
    <div class="mx-auto max-w-lg px-4 sm:px-6">
        <div class="med-card p-6 sm:p-8">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                </div>
                <h1 class="mt-4 font-heading text-2xl font-extrabold text-slate-900">Create Your Patient Account</h1>
                <p class="mt-1 text-sm text-slate-600">Track medical visa updates, appointments, and video consultations in one secure portal</p>
            </div>

            <div class="mt-6">
                <x-form-error-summary :errors="$errors" />
            </div>

            <form action="{{ route('register') }}" method="POST" class="mt-4 space-y-4" novalidate>
                @csrf

                <div>
                    <label for="field-name" class="block text-sm font-semibold text-slate-800">Full Name (As on Passport) <span class="text-red-600">*</span></label>
                    <input id="field-name" name="name" type="text" value="{{ old('name') }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20">
                    @error('name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="field-phone" class="block text-sm font-semibold text-slate-800">Mobile / WhatsApp Number <span class="text-red-600">*</span></label>
                    <input id="field-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="017XXXXXXXX" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20">
                    @error('phone') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="field-email" class="block text-sm font-semibold text-slate-800">Email Address <span class="text-red-600">*</span></label>
                    <input id="field-email" name="email" type="email" value="{{ old('email') }}" required
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20">
                    @error('email') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="field-password" class="block text-sm font-semibold text-slate-800">Password <span class="text-red-600">*</span></label>
                        <input id="field-password" name="password" type="password" autocomplete="new-password" required
                               class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20">
                        @error('password') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="field-password_confirmation" class="block text-sm font-semibold text-slate-800">Confirm Password <span class="text-red-600">*</span></label>
                        <input id="field-password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                               class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20">
                    </div>
                </div>

                <button
                    type="submit"
                    class="mt-2 w-full cursor-pointer rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
                >
                    Create Patient Account
                </button>
            </form>

            <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-bold text-cyan-800 underline hover:text-emerald-700">Sign In</a>
            </p>
        </div>
    </div>
</section>
@endsection
