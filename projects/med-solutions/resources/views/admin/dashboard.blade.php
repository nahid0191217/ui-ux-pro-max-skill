@extends('layouts.app')

@section('title', ($panel === 'staff' ? 'Staff Panel' : 'Admin Panel') . ' | Med Solutions')

@section('content')
<section class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ tab: 'requests' }">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-950">
                    Role: {{ strtoupper(auth()->user()->role->value) }} ({{ $panel === 'staff' ? 'Staff Panel' : 'Admin Panel' }})
                </span>
                <h1 class="mt-2 font-heading text-2xl font-extrabold text-slate-900">{{ $panel === 'staff' ? 'Med Solutions Care Coordinator Panel' : 'Med Solutions Admin & Coordinator Control Panel' }}</h1>
            </div>
        </div>

        <!-- KPI Metrics -->
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="med-card p-5">
                <div class="text-xs font-semibold text-slate-500">Total Requests</div>
                <div class="mt-1 font-heading text-2xl font-extrabold text-slate-900">{{ $stats['total_requests'] }}</div>
            </div>
            <div class="med-card p-5">
                <div class="text-xs font-semibold text-amber-800">Pending Leads</div>
                <div class="mt-1 font-heading text-2xl font-extrabold text-amber-700">{{ $stats['pending_requests'] }}</div>
            </div>
            <div class="med-card p-5">
                <div class="text-xs font-semibold text-cyan-800">Active Visa Files</div>
                <div class="mt-1 font-heading text-2xl font-extrabold text-cyan-800">{{ $stats['processing_visas'] }}</div>
            </div>
            <div class="med-card p-5">
                <div class="text-xs font-semibold text-emerald-800">Telemedicine Queue</div>
                <div class="mt-1 font-heading text-2xl font-extrabold text-emerald-700">{{ $stats['upcoming_consultations'] }}</div>
            </div>
            <div class="med-card p-5">
                <div class="text-xs font-semibold text-slate-500">Unread Inquiries</div>
                <div class="mt-1 font-heading text-2xl font-extrabold text-slate-900">{{ $stats['unread_contacts'] }}</div>
            </div>
        </div>

        <!-- Admin Navigation Tabs -->
        <div class="mt-8 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <button type="button" @click="tab = 'requests'" :class="tab === 'requests' ? 'bg-cyan-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'" class="cursor-pointer rounded-xl px-4 py-2.5 text-sm font-bold transition">
                1. Service Requests & Visa Status
            </button>
            <button type="button" @click="tab = 'telemedicine'" :class="tab === 'telemedicine' ? 'bg-cyan-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'" class="cursor-pointer rounded-xl px-4 py-2.5 text-sm font-bold transition">
                2. Telemedicine & Video Links
            </button>
            @if ($panel === 'admin')
            <button type="button" @click="tab = 'cms'" :class="tab === 'cms' ? 'bg-cyan-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'" class="cursor-pointer rounded-xl px-4 py-2.5 text-sm font-bold transition">
                3. Services & Content (CMS)
            </button>
            @endif
            @if ($panel === 'admin')
            <button type="button" @click="tab = 'users'" :class="tab === 'users' ? 'bg-cyan-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'" class="cursor-pointer rounded-xl px-4 py-2.5 text-sm font-bold transition">
                4. User & Role Management
            </button>
            @endif
        </div>

        <!-- TAB 1: Service Requests & Visa/Appointment Management -->
        <div x-show="tab === 'requests'" class="mt-6 space-y-4">
            @foreach ($serviceRequests as $req)
                <form action="{{ route('admin.service-requests.update', $req) }}" method="POST" class="med-card p-6">
                    @csrf
                    @method('PATCH')

                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-extrabold text-cyan-900">{{ $req->tracking_code }}</span>
                                <x-status-badge :status="$req->status" />
                            </div>
                            <h3 class="mt-1 text-base font-bold text-slate-900">{{ $req->patient_name }} ({{ $req->phone }})</h3>
                            <p class="text-xs text-slate-500">
                                Hospital: {{ $req->preferred_hospital ?? 'TBD' }} | Specialist: {{ $req->preferred_doctor ?? 'TBD' }}
                            </p>
                        </div>
                        @if ($req->medical_report_path)
                            <a href="{{ route('services.report.download', $req) }}" class="rounded-lg bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-900 ring-1 ring-cyan-200 hover:bg-cyan-100">
                                Download Medical Report
                            </a>
                        @endif
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Overall Status</label>
                            <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
                                @foreach (['pending', 'processing', 'confirmed', 'completed', 'cancelled'] as $st)
                                    <option value="{{ $st }}" @selected($req->status->value === $st)>{{ ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700">Visa Processing Status</label>
                            <select name="visa_status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
                                @foreach (['not_requested', 'documents_received', 'vil_issued', 'ivac_submitted', 'approved'] as $vs)
                                    <option value="{{ $vs }}" @selected($req->visa_status === $vs)>{{ str_replace('_', ' ', ucfirst($vs)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700">Doctor Appointment</label>
                            <select name="appointment_status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
                                @foreach (['not_requested', 'requested', 'slot_confirmed', 'completed'] as $as)
                                    <option value="{{ $as }}" @selected($req->appointment_status === $as)>{{ str_replace('_', ' ', ucfirst($as)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700">Assigned Coordinator</label>
                            <select name="assigned_staff_id" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">
                                <option value="">Unassigned</option>
                                @foreach ($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" @selected($req->assigned_staff_id === $staff->id)>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <input type="text" name="admin_notes" value="{{ $req->admin_notes }}" placeholder="Coordinator update note visible on patient status tracker (VIL, flight, pickup, stay)..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                        <button type="submit" class="cursor-pointer shrink-0 rounded-lg bg-emerald-700 px-5 py-2 text-xs font-bold text-white hover:bg-emerald-800">
                            Save Status Update
                        </button>
                    </div>
                </form>
            @endforeach

            <div class="mt-4">{{ $serviceRequests->links() }}</div>
        </div>

        <!-- TAB 2: Telemedicine Video Consultations -->
        <div x-show="tab === 'telemedicine'" class="mt-6 space-y-4">
            @foreach ($consultations as $consult)
                <form action="{{ route('admin.consultations.update', $consult) }}" method="POST" class="med-card p-5">
                    @csrf
                    @method('PATCH')
                    <div class="grid items-end gap-4 sm:grid-cols-12">
                        <div class="sm:col-span-4">
                            <span class="text-xs font-bold text-cyan-800">{{ $consult->stageLabel() }}</span>
                            <h3 class="font-bold text-slate-900">{{ $consult->patient_name }} ({{ $consult->phone }})</h3>
                            <p class="text-xs text-slate-500">{{ $consult->department }} • {{ $consult->preferred_date->format('d M Y, h:i A') }}</p>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                @foreach (['pending', 'scheduled', 'completed', 'cancelled'] as $cst)
                                    <option value="{{ $cst }}" @selected($consult->status === $cst)>{{ ucfirst($cst) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700">Google Meet / Zoom Link</label>
                            <input type="url" name="meeting_link" value="{{ $consult->meeting_link }}" placeholder="https://meet.google.com/..." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="w-full cursor-pointer rounded-lg bg-cyan-800 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-900">Save Link</button>
                        </div>
                    </div>
                </form>
            @endforeach
        </div>

@if ($panel === 'admin')
        <!-- TAB 3: CMS Services Management -->
        <div x-show="tab === 'cms'" class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ($services as $svc)
                <form action="{{ route('admin.services.update', $svc) }}" method="POST" class="med-card p-5 space-y-3">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="title" value="{{ $svc->title }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold">
                    <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ $svc->description }}</textarea>
                    <div class="flex items-center justify-between">
                        <select name="is_active" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
                            <option value="1" @selected($svc->is_active)>Active (Visible)</option>
                            <option value="0" @selected(! $svc->is_active)>Inactive (Hidden)</option>
                        </select>
                        <button type="submit" class="cursor-pointer rounded-lg bg-cyan-800 px-4 py-1.5 text-xs font-bold text-white">Update Service</button>
                    </div>
                </form>
            @endforeach
        </div>

@endif
@if ($panel === 'admin')
        <!-- TAB 4: User & Role Management -->
        <div x-show="tab === 'users'" class="med-card mt-6 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="px-6 py-3">Full Name</th>
                        <th class="px-6 py-3">Email & Phone</th>
                        <th class="px-6 py-3">Current Role</th>
                        <th class="px-6 py-3">Change Role (Admin Only)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $u)
                        <tr>
                            <td class="px-6 py-3.5 font-semibold">{{ $u->name }}</td>
                            <td class="px-6 py-3.5 text-xs text-slate-600">{{ $u->email }} • {{ $u->phone }}</td>
                            <td class="px-6 py-3.5"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase">{{ $u->role->value }}</span></td>
                            <td class="px-6 py-3.5">
                                @if (auth()->user()->isAdmin())
                                    <form action="{{ route('admin.users.update-role', $u) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs">
                                            @foreach (['patient', 'staff', 'admin'] as $roleOpt)
                                                <option value="{{ $roleOpt }}" @selected($u->role->value === $roleOpt)>{{ ucfirst($roleOpt) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="cursor-pointer rounded-lg bg-slate-900 px-3 py-1 text-xs font-bold text-white">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
@endif
    </div>
</section>
@endsection
