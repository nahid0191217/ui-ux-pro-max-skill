<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Contact;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render Admin / Staff Portal Dashboard with KPI metrics, patient leads, telemedicine & CMS.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');

        $stats = [
            'total_requests' => ServiceRequest::count(),
            'pending_requests' => ServiceRequest::where('status', RequestStatus::Pending)->count(),
            'processing_visas' => ServiceRequest::whereIn('visa_status', ['documents_received', 'vil_issued', 'ivac_submitted'])->count(),
            'upcoming_consultations' => Consultation::whereIn('status', ['pending', 'scheduled'])->count(),
            'unread_contacts' => Contact::where('is_read', false)->count(),
        ];

        $serviceRequests = ServiceRequest::with(['user', 'assignedStaff'])
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $consultations = Consultation::with('user')
            ->latest()
            ->take(10)
            ->get();

        $contacts = Contact::latest()->take(10)->get();
        $staffMembers = User::whereIn('role', [UserRole::Admin, UserRole::Staff])->get();
        $users = User::latest()->take(15)->get();
        $services = Service::orderBy('sort_order')->get();
        $testimonials = Testimonial::latest()->take(6)->get();

        return view('admin.dashboard', compact(
            'stats',
            'serviceRequests',
            'consultations',
            'contacts',
            'staffMembers',
            'users',
            'services',
            'testimonials',
            'statusFilter'
        ));
    }

    /**
     * Update Service Request status, Visa status, Appointment status & staff assignment.
     */
    public function updateServiceRequest(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'confirmed', 'completed', 'cancelled'])],
            'visa_status' => ['required', Rule::in(['not_requested', 'documents_received', 'vil_issued', 'ivac_submitted', 'approved'])],
            'appointment_status' => ['required', Rule::in(['not_requested', 'requested', 'slot_confirmed', 'completed'])],
            'assigned_staff_id' => ['nullable', 'exists:users,id'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $serviceRequest->update($validated);

        return back()->with('success', "Status updated successfully for tracking code {$serviceRequest->tracking_code}.");
    }

    /**
     * Schedule Telemedicine consultation & attach Google Meet / Zoom video link.
     */
    public function updateConsultation(Request $request, Consultation $consultation): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'scheduled', 'completed', 'cancelled'])],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'preferred_date' => ['nullable', 'date'],
        ]);

        $consultation->update(array_filter($validated, fn ($v) => $v !== null));

        return back()->with('success', "Telemedicine session updated for {$consultation->patient_name}.");
    }

    /**
     * Update User Role (Admin only).
     */
    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Only Administrators may modify user roles.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'staff', 'patient'])],
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "Role updated for {$user->name}.");
    }

    /**
     * Toggle or update CMS Service item & flush homepage cache.
     */
    public function updateServiceContent(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $service->update($validated);
        Cache::forget('home.services');

        return back()->with('success', "Service '{$service->title}' has been updated.");
    }
}
