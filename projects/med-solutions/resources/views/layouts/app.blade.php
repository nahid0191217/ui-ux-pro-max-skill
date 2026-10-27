<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Med Solutions | Trusted Bangladesh-to-India Medical Tourism Assistance')</title>
    <meta name="description" content="Med Solutions - End-to-end assistance for Bangladeshi patients travelling to India for specialist appointments, medical visas, telemedicine, airport pickup, and hospital-near accommodation.">

    <link rel="icon" type="image/jpeg" href="{{ asset('images/med-solutions-logo.jpeg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased selection:bg-brand-100 selection:text-brand-950">
    <!-- Skip Link for Keyboard Accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-800 focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold text-white focus:shadow-lg">
        Skip to main content
    </a>

    <!-- Top Support Bar -->
    <div class="bg-slate-900 text-slate-100 text-xs sm:text-sm">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5 font-medium text-accent-300">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    24/7 Patient Helpline: +880 XXXX-XXXXXX
                </span>
                <span class="hidden md:inline text-slate-400">|</span>
                <span class="hidden md:inline text-slate-300">Care Network: Bangladesh → India</span>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                @if (config('demo.role_switcher') && ! app()->isProduction())
                    <form action="{{ route('demo.switch-role') }}" method="POST" class="flex flex-wrap items-center gap-1.5">
                        @csrf
                        <span class="text-slate-400">Quick Role Switcher (Demo):</span>
                        @foreach (['admin' => 'Admin', 'staff' => 'Staff', 'patient' => 'Patient'] as $roleKey => $roleLabel)
                            <button type="submit" name="role" value="{{ $roleKey }}" class="cursor-pointer rounded-md px-2.5 py-1 font-semibold transition {{ auth()->check() && auth()->user()->role->value === $roleKey ? 'bg-emerald-700 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">{{ $roleLabel }}</button>
                        @endforeach
                    </form>
                @endif
                <a href="{{ route('services.track') }}" class="cursor-pointer font-medium text-emerald-300 hover:text-emerald-200 underline-offset-4 hover:underline">
                    Track Request Status
                </a>
            </div>
        </div>
    </div>

    <!-- Sticky Main Header -->
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-brand-100 bg-white/95 backdrop-blur-md">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8" aria-label="Main Navigation">
            <a href="{{ route('home') }}" class="flex items-center gap-3 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 rounded-lg">
                <img src="{{ asset('images/med-solutions-logo.jpeg') }}" alt="Med Solutions logo" class="h-12 w-auto sm:h-14">
                <div class="hidden sm:block leading-tight">
                    <span class="block font-heading text-lg font-extrabold tracking-tight text-brand-900">Med Solutions</span>
                    <span class="block text-[11px] font-semibold uppercase tracking-widest text-accent-600">Cross-Border Medical Care</span>
                </div>
            </a>

            <!-- Desktop Links -->
            <div class="hidden lg:flex lg:items-center lg:gap-7 text-sm font-semibold text-slate-700">
                <a href="{{ route('home') }}#about" class="cursor-pointer transition-colors hover:text-brand-800">About Us</a>
                <a href="{{ route('home') }}#services" class="cursor-pointer transition-colors hover:text-brand-800">Services</a>
                <a href="{{ route('home') }}#pre-travel-guide" class="cursor-pointer transition-colors hover:text-brand-800">Pre-Travel Guide</a>
                <a href="{{ route('consultations.create') }}" class="cursor-pointer transition-colors hover:text-brand-800">Telemedicine</a>
                <a href="{{ route('home') }}#contact" class="cursor-pointer transition-colors hover:text-brand-800">Contact</a>
            </div>

            <!-- Authenticated vs Guest Action Buttons -->
            <div class="hidden lg:flex lg:items-center lg:gap-3">
                @auth
                    <a href="{{ route(auth()->user()->panelRoute()) }}" class="cursor-pointer rounded-xl border border-brand-700 px-3.5 py-2 text-xs font-bold text-brand-900 transition-colors hover:bg-brand-50">
                        {{ auth()->user()->isAdmin() ? 'Admin Panel' : (auth()->user()->isStaff() ? 'Staff Panel' : 'My Dashboard') }} ({{ auth()->user()->name }})
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="cursor-pointer rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                            Sign Out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="cursor-pointer rounded-xl px-3.5 py-2 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="cursor-pointer rounded-xl border border-brand-700 px-3.5 py-2 text-sm font-semibold text-brand-800 transition-colors hover:bg-brand-50">
                        Register
                    </a>
                @endauth
                <a href="{{ route('services.create') }}" class="cursor-pointer inline-flex items-center gap-2 rounded-xl btn-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">
                    <span>Request Assistance</span>
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <!-- Mobile Menu Toggle -->
            <button
                type="button"
                @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen.toString()"
                aria-controls="mobile-menu"
                aria-label="Toggle navigation menu"
                class="inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 text-slate-700 lg:hidden"
            >
                <svg x-show="!mobileOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileOpen" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </nav>

        <!-- Mobile Drawer -->
        <div id="mobile-menu" x-show="mobileOpen" x-transition class="border-t border-slate-100 bg-white px-4 pb-5 pt-3 lg:hidden">
            <div class="flex flex-col space-y-2 text-sm font-semibold text-slate-700">
                <a href="{{ route('home') }}#about" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">About Us</a>
                <a href="{{ route('home') }}#services" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Core Services</a>
                <a href="{{ route('home') }}#pre-travel-guide" @click="mobileOpen = false" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Pre-Travel Guide</a>
                <a href="{{ route('consultations.create') }}" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Telemedicine Booking</a>
                <a href="{{ route('services.track') }}" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Track Request</a>
                @auth
                    <a href="{{ route(auth()->user()->panelRoute()) }}" class="rounded-lg px-3 py-2.5 text-brand-800 hover:bg-brand-50">My Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-red-700 hover:bg-red-50">Sign Out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Sign In</a>
                    <a href="{{ route('register') }}" class="rounded-lg px-3 py-2.5 hover:bg-brand-50">Create Account</a>
                @endauth
                <a href="{{ route('services.create') }}" class="mt-2 rounded-xl bg-emerald-700 px-4 py-3 text-center text-white">Request Assistance</a>
            </div>
        </div>
    </header>

    <!-- Global Flash Message -->
    @if (session('success'))
        <div role="status" aria-live="polite" class="mx-auto mt-4 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3.5 text-sm font-medium text-emerald-950 shadow-sm">
                <svg class="h-5 w-5 shrink-0 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Page Content -->
    <main id="main-content">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-slate-950 text-slate-300">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <div class="inline-block rounded-xl bg-white p-2">
                        <img src="{{ asset('images/med-solutions-logo.jpeg') }}" alt="Med Solutions logo" class="h-16 w-auto">
                    </div>
                    <p class="mt-4 text-sm leading-relaxed text-slate-400">
                        Your trusted medical tourism partner helping Bangladeshi patients access world-class hospitals across India with end-to-end care coordination.
                    </p>
                    <div class="mt-5 flex items-center gap-3">
                        <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook Page" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg>
                        </a>
                        <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube Channel" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.58 7.19a2.51 2.51 0 0 0-1.77-1.77C18.25 5 12 5 12 5s-6.25 0-7.81.42c-.86.23-1.54.91-1.77 1.77C2 8.75 2 12 2 12s0 3.25.42 4.81c.23.86.91 1.54 1.77 1.77C5.75 19 12 19 12 19s6.25 0 7.81-.42a2.51 2.51 0 0 0 1.77-1.77C22 15.25 22 12 22 12s0-3.25-.42-4.81zM10 15V9l5.2 3-5.2 3z"/></svg>
                        </a>
                        <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn Profile" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-slate-900 text-slate-300 ring-1 ring-slate-800 transition hover:bg-brand-700 hover:text-white">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Quick Links</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        <li><a href="{{ route('home') }}#pre-travel-guide" class="hover:text-accent-400">Pre-Travel India Checklist</a></li>
                        <li><a href="{{ route('services.track') }}" class="hover:text-accent-400">Live Request Tracking</a></li>
                        <li><a href="{{ route('login') }}" class="hover:text-accent-400">Account Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-accent-400">Patient Registration</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-white">Contact Us</h3>
                    <ul class="mt-4 space-y-3 text-sm text-slate-400">
                        <li>Office address: [add your office address]</li>
                        <li>Phone: +880 XXXX-XXXXXX</li>
                        <li>Email: support@example.com</li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 border-t border-slate-800 pt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Med Solutions. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
