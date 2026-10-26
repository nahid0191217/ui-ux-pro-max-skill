<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'user_id',
        'patient_name',
        'phone',
        'email',
        'service_type',
        'selected_services',
        'preferred_hospital',
        'preferred_doctor',
        'details',
        'medical_report_path',
        'medical_report_original_name',
        'status',
        'visa_status',
        'appointment_status',
        'assigned_staff_id',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'selected_services' => 'array',
            'status' => RequestStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceRequest $request) {
            if (empty($request->tracking_code)) {
                $request->tracking_code = 'MS-' . now()->format('Y') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public static function serviceLabels(): array
    {
        return [
            'appointment' => 'Doctor Appointment',
            'visa_processing' => 'Indian Visa, VIL & Ticket Processing',
            'telemedicine' => 'Telemedicine Video Consultation',
            'treatment_plan' => 'Treatment Plan & Cost Estimate',
            'pickup_drop' => 'Airport/Station Pickup, Drop & Bangla Interpreter',
            'accommodation' => 'Affordable Hospital-Near Accommodation (Self-Cooking)',
        ];
    }
}
