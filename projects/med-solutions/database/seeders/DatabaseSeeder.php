<?php

namespace Database\Seeders;

use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\BlogPost;
use App\Models\Consultation;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed Med Solutions initial English data.
     */
    public function run(): void
    {
        // 1. Role-based Users (Admin, Staff, Patient)
        $admin = User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Dr. Mahmudul Hasan (Admin)',
            'phone' => '+8801700000001',
            'role' => UserRole::Admin,
            'password' => Hash::make('Admin@Med2026!'),
            'email_verified_at' => now(),
        ]);

        $staff = User::updateOrCreate(['email' => 'staff@example.com'], [
            'name' => 'Farhana Rahman (Care Coordinator)',
            'phone' => '+8801700000002',
            'role' => UserRole::Staff,
            'password' => Hash::make('Staff@Med2026!'),
            'email_verified_at' => now(),
        ]);

        $patient = User::updateOrCreate(['email' => 'patient@example.com'], [
            'name' => 'Md. Kamrul Islam',
            'phone' => '01712987654',
            'role' => UserRole::Patient,
            'password' => Hash::make('Patient@Med2026!'),
            'email_verified_at' => now(),
        ]);

        // 2. Core 6 Services of Med Solutions (100% English)
        $services = [
            [
                'slug' => 'appointment',
                'title' => 'Doctor Appointments',
                'subtitle' => 'Specialist Matching & Priority Slots',
                'description' => 'Expert assistance in finding top specialist doctors in the required hospital department across India and securing priority appointments.',
                'icon_name' => 'calendar-check',
                'sort_order' => 1,
            ],
            [
                'slug' => 'visa_processing',
                'title' => 'Visa Processing & Tickets',
                'subtitle' => 'VIL, Indian MED Visa & Travel',
                'description' => 'End-to-end Indian Medical Visa processing, official hospital Visa Invitation Letters (VIL), and confirmed air and train ticketing.',
                'icon_name' => 'passport',
                'sort_order' => 2,
            ],
            [
                'slug' => 'telemedicine',
                'title' => 'Telemedicine Consultations',
                'subtitle' => 'Pre-Travel & Post-Return Video Care',
                'description' => 'Direct video conferencing with Indian specialist doctors before travelling from Bangladesh and seamless follow-up consultations after returning home.',
                'icon_name' => 'video',
                'sort_order' => 3,
            ],
            [
                'slug' => 'treatment_plan',
                'title' => 'Treatment Plan & Cost Estimate',
                'subtitle' => 'Transparent Clinical Roadmap',
                'description' => 'Clear pre-travel guidance on optimal medical procedures, expected hospital stay duration, and transparent treatment cost estimates.',
                'icon_name' => 'clipboard-pulse',
                'sort_order' => 4,
            ],
            [
                'slug' => 'pickup_drop',
                'title' => 'Pickup, Drop & Interpreter',
                'subtitle' => 'Arrival Logistics & Language Assistance',
                'description' => 'Reliable patient pickup and drop-off from Indian airports and railway stations, paired with dedicated Bengali-speaking medical interpreters.',
                'icon_name' => 'car',
                'sort_order' => 5,
            ],
            [
                'slug' => 'accommodation',
                'title' => 'Hospital-Near Accommodation',
                'subtitle' => 'Affordable Stay with Self-Cooking',
                'description' => 'Clean, safe, and budget-friendly guest houses and apartments with self-cooking kitchen facilities located within walking distance of the hospital.',
                'icon_name' => 'home-heart',
                'sort_order' => 6,
            ],
        ];

        foreach ($services as $svc) {
            Service::updateOrCreate(['slug' => $svc['slug']], $svc);
        }

        // 3. Verified Patient Testimonials
        $testimonials = [
            [
                'patient_name' => 'Alhaj Rafiqul Alam',
                'district' => 'Uttara, Dhaka',
                'hospital_treated' => 'Apollo Hospitals, Chennai',
                'quote' => 'From issuing the Visa Invitation Letter for my father’s cardiac bypass surgery to Chennai airport pickup and arranging an apartment with a kitchen right beside Apollo—Med Solutions handled everything seamlessly.',
                'rating' => 5,
            ],
            [
                'patient_name' => 'Nusrat Jahan Chowdhury',
                'district' => 'Sylhet',
                'hospital_treated' => 'Manipal Hospital, Bangalore',
                'quote' => 'Our pre-travel video consultation gave us an accurate cost estimate before leaving Bangladesh, and we easily completed our post-surgery follow-up online after returning home.',
                'rating' => 5,
            ],
            [
                'patient_name' => 'Md. Saiful Islam',
                'district' => 'Chattogram',
                'hospital_treated' => 'CMC Vellore',
                'quote' => 'We were worried about language barriers in Vellore, but Med Solutions provided a dedicated Bengali-speaking coordinator and priority doctor appointments that made our entire treatment stress-free.',
                'rating' => 5,
            ],
        ];

        foreach ($testimonials as $item) {
            Testimonial::firstOrCreate(['patient_name' => $item['patient_name']], $item);
        }

        // 4. Sample Service Request & Telemedicine Consultation
        ServiceRequest::updateOrCreate(['tracking_code' => 'MS-2026-DEMO1'], [
            'user_id' => $patient->id,
            'patient_name' => $patient->name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'service_type' => 'appointment',
            'selected_services' => ['appointment', 'visa_processing', 'pickup_drop', 'accommodation'],
            'preferred_hospital' => 'Apollo Hospitals, Chennai',
            'preferred_doctor' => 'Dr. Refai Showkathali (Interventional Cardiology)',
            'details' => 'Coronary angiogram report attached. Need urgent Medical Visa Invitation Letter for patient + 1 attendant.',
            'status' => RequestStatus::Confirmed,
            'visa_status' => 'vil_issued',
            'appointment_status' => 'slot_confirmed',
            'assigned_staff_id' => $staff->id,
            'admin_notes' => 'Official VIL emailed to patient; Chennai airport pickup & hospital-near guest house reserved.',
        ]);

        Consultation::firstOrCreate([
            'user_id' => $patient->id,
            'preferred_doctor' => 'Dr. Devi Prasad Shetty Team',
        ], [
            'patient_name' => $patient->name,
            'phone' => $patient->phone,
            'email' => $patient->email,
            'consultation_stage' => 'pre_travel',
            'department' => 'Cardiology & Cardiac Surgery',
            'preferred_date' => now()->addDays(2)->setTime(19, 30),
            'preferred_time_slot' => 'Evening (7:30 PM BST)',
            'symptoms_notes' => 'Pre-travel review of ECHO and Coronary Angiogram reports.',
            'status' => 'scheduled',
            'meeting_link' => 'https://meet.google.com/med-soln-care',
        ]);

        BlogPost::updateOrCreate(['slug' => 'india-medical-visa-checklist-bangladesh'], [
            'title' => 'Complete Indian Medical Visa (MED Visa) Checklist for Bangladeshi Patients',
            'category' => 'Visa & Travel Guide',
            'excerpt' => 'Step-by-step instructions on obtaining a hospital Visa Invitation Letter (VIL), local referral notes, and attendant documentation.',
            'content' => 'Applying accurately for an Indian Medical Visa ensures rapid processing at IVAC centers across Bangladesh...',
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
