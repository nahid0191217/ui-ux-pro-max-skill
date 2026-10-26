<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allows both authenticated patients & direct guest submissions
    }

    public function rules(): array
    {
        $allowedServices = [
            'appointment',
            'visa_processing',
            'telemedicine',
            'treatment_plan',
            'pickup_drop',
            'accommodation',
        ];

        return [
            'patient_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^(\+?8801|01)[3-9]\d{8}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:150'],
            'selected_services' => ['required', 'array', 'min:1'],
            'selected_services.*' => ['required', 'string', Rule::in($allowedServices)],
            'preferred_hospital' => ['nullable', 'string', 'max:200'],
            'preferred_doctor' => ['nullable', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:3000'],
            'medical_report' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240', // Max 10 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required' => 'Please enter the patient’s full name as shown on the passport.',
            'phone.required' => 'Please enter a valid phone or WhatsApp number.',
            'phone.regex' => 'Please enter a valid Bangladeshi mobile number (e.g. 017XXXXXXXX or +88017XXXXXXXX).',
            'selected_services.required' => 'Please select at least one assistance service.',
            'medical_report.mimes' => 'Medical reports must be uploaded in PDF, JPG, PNG, or WEBP format.',
            'medical_report.max' => 'Medical report file size must not exceed 10 MB.',
        ];
    }
}
