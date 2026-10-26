@extends('layouts.app')

@section('title', 'Sign In | Med Solutions')

@section('content')
<section class="py-12 sm:py-20">
    <div class="mx-auto max-w-md px-4 sm:px-6">
        <div class="med-card p-6 sm:p-8" x-data="{ showPassword: false }">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-800 text-white shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h1 class="mt-4 font-heading text-2xl font-extrabold text-slate-900">Sign In to Your Account</h1>
                <p class="mt-1 text-sm text-slate-600">Access your patient dashboard or coordinator portal</p>
            </div>

            <div class="mt-6">
                <x-form-error-summary :errors="$errors" />
            </div>

            <form action="{{ route('login') }}" method="POST" class="mt-4 space-y-5" novalidate>
                @csrf

                <div>
                    <label for="field-email" class="block text-sm font-semibold text-slate-800">
                        Email Address <span class="text-red-600">*</span>
                    </label>
                    <input
                        id="field-email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        value="{{ old('email') }}"
                        required
                        @error('email') aria-invalid="true" aria-describedby="err-login-email" @enderror
                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20"
                    >
                    @error('email')
                        <p id="err-login-email" class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="field-password" class="block text-sm font-semibold text-slate-800">
                            Password <span class="text-red-600">*</span>
                        </label>
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="cursor-pointer text-xs font-semibold text-cyan-800 hover:text-cyan-950"
                            x-text="showPassword ? 'Hide' : 'Show'"
                        >Show</button>
                    </div>
                    <input
                        id="field-password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        required
                        class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-700/20"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-cyan-800 focus:ring-cyan-700">
                        <span>Keep me signed in</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full cursor-pointer rounded-xl bg-cyan-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyan-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-700 focus-visible:ring-offset-2"
                >
                    Sign In Securely
                </button>
            </form>

            <p class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
                Don’t have a patient account yet?
                <a href="{{ route('register') }}" class="font-bold text-cyan-800 underline hover:text-emerald-700">Create an account</a>
            </p>
        </div>
    </div>
</section>
@endsection
