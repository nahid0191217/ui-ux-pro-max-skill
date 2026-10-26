<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for `service_requests` table (MySQL).
     */
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code', 24)->unique()->comment('Public status tracking ID e.g. MS-2026-4821');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Patient contact fields (supports both authenticated users & direct guest bookings)
            $table->string('patient_name');
            $table->string('phone', 25)->index();
            $table->string('email')->nullable()->index();

            // Service & medical preferences
            $table->enum('service_type', [
                'appointment',
                'visa_processing',
                'telemedicine',
                'treatment_plan',
                'pickup_drop',
                'accommodation',
            ])->index();
            $table->json('selected_services')->nullable()->comment('Array of multi-selected assistance services');
            $table->string('preferred_hospital')->nullable();
            $table->string('preferred_doctor')->nullable();
            $table->text('details')->nullable();

            // Private encrypted/signed medical report storage path
            $table->string('medical_report_path')->nullable();
            $table->string('medical_report_original_name')->nullable();

            // Granular workflow tracking for Admin & Staff
            $table->enum('status', [
                'pending',
                'processing',
                'confirmed',
                'completed',
                'cancelled',
            ])->default('pending')->index();

            $table->enum('visa_status', [
                'not_requested',
                'documents_received',
                'vil_issued',
                'ivac_submitted',
                'approved',
            ])->default('not_requested');

            $table->enum('appointment_status', [
                'not_requested',
                'requested',
                'slot_confirmed',
                'completed',
            ])->default('not_requested');

            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
