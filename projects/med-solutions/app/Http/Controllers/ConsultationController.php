<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    /**
     * Show Telemedicine / Video Consultation booking page.
     */
    public function create(): View
    {
        $departments = [
            'Cardiology & Cardiac Surgery',
            'Medical & Surgical Oncology (Cancer Care)',
            'Neurology & Neurosurgery',
            'Gastroenterology & Liver Transplant',
            'Nephrology & Kidney Transplant',
            'Orthopedics, Joint & Spine Surgery',
            'Gynecology, Fertility & IVF',
            'Pediatrics & Pediatric Cardiac Care',
        ];

        return view('consultations.create', compact('departments'));
    }

    /**
     * Store Telemedicine consultation request (pre-travel or post-return).
     */
    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $reportPath = null;
        if ($request->hasFile('medical_report')) {
            $reportPath = $request->file('medical_report')
                ->store('consultation-reports/' . now()->format('Y-m'), 'local');
        }

        Consultation::create([
            'user_id' => $request->user()?->id,
            'patient_name' => $validated['patient_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? $request->user()?->email,
            'consultation_stage' => $validated['consultation_stage'],
            'department' => $validated['department'],
            'preferred_doctor' => $validated['preferred_doctor'] ?? null,
            'preferred_date' => $validated['preferred_date'],
            'preferred_time_slot' => $validated['preferred_time_slot'] ?? 'Evening (6:00 PM - 9:00 PM BST)',
            'symptoms_notes' => $validated['symptoms_notes'] ?? null,
            'medical_report_path' => $reportPath,
            'status' => 'pending',
        ]);

        return back()->with(
            'success',
            'Your Telemedicine video consultation request has been received! Once the doctor confirms the slot, we will share your meeting link via SMS and email.'
        );
    }
}
