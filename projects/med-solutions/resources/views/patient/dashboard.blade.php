@extends('layouts.app')

@section('title', 'Patient Portal Dashboard | Med Solutions')

@section('content')
<section class="py-10 sm:py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="font-heading text-2xl font-extrabold text-slate-900">Welcome, {{ $user->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Monitor your medical assistance requests, visa processing updates, and telemedicine video links.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('services.create') }}" class="cursor-pointer rounded-xl btn-brand px-4 py-2.5 text-sm font-bold text-white ">+ New Service Request</a>
                <a href="{{ route('consultations.create') }}" class="cursor-pointer rounded-xl border border-brand-700 bg-white px-4 py-2.5 text-sm font-bold text-brand-900 hover:bg-brand-50">Book Telemedicine</a>
            </div>
        </div>

        <!-- Service Requests Table -->
        <div class="med-card mt-8 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-heading text-lg font-bold text-slate-900">Your Service Requests</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                        <tr>
                            <th class="px-6 py-3.5">Tracking Code</th>
                            <th class="px-6 py-3.5">Hospital & Services</th>
                            <th class="px-6 py-3.5">Visa Status</th>
                            <th class="px-6 py-3.5">Appointment</th>
                            <th class="px-6 py-3.5">Overall Status</th>
                            <th class="px-6 py-3.5">Medical Report</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($serviceRequests as $req)
                            <tr>
                                <td class="px-6 py-4 font-mono font-bold text-brand-900">{{ $req->tracking_code }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $req->preferred_hospital ?? 'Hospital Under Review' }}</div>
                                    <div class="text-xs text-slate-500">{{ implode(', ', $req->selected_services ?? [$req->service_type]) }}</div>
                                </td>
                                <td class="px-6 py-4"><x-status-badge :status="$req->visa_status" /></td>
                                <td class="px-6 py-4"><x-status-badge :status="$req->appointment_status" /></td>
                                <td class="px-6 py-4"><x-status-badge :status="$req->status" /></td>
                                <td class="px-6 py-4">
                                    @if ($req->medical_report_path)
                                        <a href="{{ route('services.report.download', $req) }}" class="text-xs font-bold text-brand-800 underline hover:text-brand-950">Download</a>
                                    @else
                                        <span class="text-xs text-slate-400">None</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">No service requests submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Telemedicine Consultations -->
        <div class="med-card mt-8 overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-heading text-lg font-bold text-slate-900">Telemedicine Video Consultations</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($consultations as $consult)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <span class="text-xs font-bold text-brand-800">{{ $consult->stageLabel() }}</span>
                            <h3 class="font-bold text-slate-900">{{ $consult->department }} {{ $consult->preferred_doctor ? '— '.$consult->preferred_doctor : '' }}</h3>
                            <p class="text-xs text-slate-500">Scheduled Date: {{ $consult->preferred_date->format('d M Y, h:i A') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-status-badge :status="$consult->status" />
                            @if ($consult->meeting_link)
                                <a href="{{ $consult->meeting_link }}" target="_blank" rel="noopener" class="cursor-pointer rounded-xl bg-brand-800 px-4 py-2 text-xs font-bold text-white hover:bg-brand-900">
                                    Join Video Call
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-6 py-8 text-center text-sm text-slate-500">No telemedicine consultations scheduled.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
