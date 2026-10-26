<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'patient_name',
        'phone',
        'email',
        'consultation_stage',
        'department',
        'preferred_doctor',
        'preferred_date',
        'preferred_time_slot',
        'symptoms_notes',
        'medical_report_path',
        'status',
        'meeting_link',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stageLabel(): string
    {
        return $this->consultation_stage === 'post_return'
            ? 'Post-Return Follow-Up Consultation'
            : 'Pre-Travel Specialist Consultation';
    }
}
