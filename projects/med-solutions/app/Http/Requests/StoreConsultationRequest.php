<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^(\+?8801|01)[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'consultation_stage' => ['required', Rule::in(['pre_travel', 'post_return'])],
            'department' => ['required', 'string', 'max:150'],
            'preferred_doctor' => ['nullable', 'string', 'max:200'],
            'preferred_date' => ['required', 'date', 'after:now'],
            'preferred_time_slot' => ['nullable', 'string', 'max:50'],
            'symptoms_notes' => ['nullable', 'string', 'max:2000'],
            'medical_report' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required' => 'Please enter the patient’s full name.',
            'phone.regex' => 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).',
            'preferred_date.after' => 'Please select a future date and time for the video consultation.',
        ];
    }
}
