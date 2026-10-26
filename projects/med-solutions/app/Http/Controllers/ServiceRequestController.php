<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\StoreServiceRequest;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiceRequestController extends Controller
{
    /**
     * Show the Service Request / Multi-Service Booking form.
     */
    public function create(Request $request): View
    {
        $preselectedService = $request->query('service', 'appointment');
        $serviceLabels = ServiceRequest::serviceLabels();

        $partnerHospitals = [
            'Apollo Hospitals (Chennai / Kolkata / New Delhi)',
            'Manipal Hospitals (Bangalore / Kolkata)',
            'Narayana Health / Rabindranath Tagore Hospital',
            'CMC Vellore (Christian Medical College)',
            'Fortis Memorial Research Institute (Gurgaon)',
            'Medanta - The Medicity (Gurgaon)',
            'Tata Memorial / HCG Cancer Centre',
            'AIG Hospitals (Hyderabad)',
        ];

        return view('services.create', compact('preselectedService', 'serviceLabels', 'partnerHospitals'));
    }

    /**
     * Store a new patient service request with secure private medical report upload.
     */
    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $selectedServices = $validated['selected_services'];

        $reportPath = null;
        $originalName = null;

        if ($request->hasFile('medical_report')) {
            $file = $request->file('medical_report');
            $originalName = $file->getClientOriginalName();
            // Store outside public root on private disk for patient confidentiality
            $reportPath = $file->store('medical-reports/' . now()->format('Y-m'), 'local');
        }

        $hasVisa = in_array('visa_processing', $selectedServices, true);
        $hasAppointment = in_array('appointment', $selectedServices, true);

        $serviceRequest = ServiceRequest::create([
            'user_id' => $request->user()?->id,
            'patient_name' => $validated['patient_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? $request->user()?->email,
            'service_type' => $selectedServices[0],
            'selected_services' => $selectedServices,
            'preferred_hospital' => $validated['preferred_hospital'] ?? null,
            'preferred_doctor' => $validated['preferred_doctor'] ?? null,
            'details' => $validated['details'] ?? null,
            'medical_report_path' => $reportPath,
            'medical_report_original_name' => $originalName,
            'status' => RequestStatus::Pending,
            'visa_status' => $hasVisa ? 'documents_received' : 'not_requested',
            'appointment_status' => $hasAppointment ? 'requested' : 'not_requested',
        ]);

        // Grant this browser session access to track its own request (phone is never placed in the URL).
        $request->session()->push('granted_tracking_codes', $serviceRequest->tracking_code);

        return redirect()
            ->route('services.track', ['tracking_code' => $serviceRequest->tracking_code])
            ->with('success', "Your service request has been submitted! Your Tracking Code is: {$serviceRequest->tracking_code}");
    }

    /**
     * Track service request status using Tracking Code + Phone Verification (or Authenticated Owner/Staff).
     */
    public function track(Request $request): View
    {
        $trackingCode = trim((string) $request->query('tracking_code', ''));
        $phoneInput = preg_replace('/\D+/', '', (string) $request->query('phone', ''));
        $serviceRequest = null;
        $verificationError = false;

        if ($trackingCode !== '') {
            $candidate = ServiceRequest::with('assignedStaff')
                ->where('tracking_code', strtoupper($trackingCode))
                ->first();

            if ($candidate) {
                $user = $request->user();
                $isAuthorizedUser = $user && (
                    $user->canManagePortal()
                    || $candidate->user_id === $user->id
                    || $this->samePhone($candidate->phone, $user->phone)
                );
                $isGrantedThisSession = in_array(
                    $candidate->tracking_code,
                    (array) $request->session()->get('granted_tracking_codes', []),
                    true
                );
                $phoneMatches = $this->samePhone($candidate->phone, $phoneInput);

                if ($isAuthorizedUser || $isGrantedThisSession || $phoneMatches) {
                    $serviceRequest = $candidate;
                } else {
                    $verificationError = true;
                }
            }
        }

        return view('services.track', compact('serviceRequest', 'trackingCode', 'phoneInput', 'verificationError'));
    }

    /**
     * Stream private medical report via authorized signed URL.
     */
    public function downloadReport(Request $request, ServiceRequest $serviceRequest): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            $this->authorize('downloadMedicalReport', $serviceRequest);
        }

        abort_if(
            ! $serviceRequest->medical_report_path || ! Storage::disk('local')->exists($serviceRequest->medical_report_path),
            404,
            'Medical report file not found.'
        );

        return Storage::disk('local')->download(
            $serviceRequest->medical_report_path,
            $serviceRequest->medical_report_original_name ?? basename($serviceRequest->medical_report_path)
        );
    }

    /** Normalise a phone number to its last 10 digits (Bangladeshi mobile numbers are 10 significant digits). */
    private function last10(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return strlen($digits) >= 10 ? substr($digits, -10) : '';
    }

    private function samePhone(?string $stored, ?string $supplied): bool
    {
        $a = $this->last10($stored);

        return $a !== '' && $a === $this->last10($supplied);
    }
}
